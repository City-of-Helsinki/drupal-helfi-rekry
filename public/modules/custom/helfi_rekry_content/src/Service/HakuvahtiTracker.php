<?php

declare(strict_types=1);

namespace Drupal\helfi_rekry_content\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\taxonomy\TermInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Save hakuvahti filter selections to database.
 */
class HakuvahtiTracker {

  use StringTranslationTrait;

  /**
   * Csv header fields which matches the database fields.
   */
  private const FIELDS = ['token', 'filter_name', 'filter_value', 'created_at'];

  /**
   * The csv headers.
   */
  private const CSV_HEADERS = ['id', 'tunniste', 'suodatin', 'valittu arvo', 'luontiaika'];

  /**
   * The term storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private EntityStorageInterface $termStorage;

  public function __construct(
    private readonly Connection $connection,
    #[Autowire(service: 'logger.channel.helfi_rekry_content')]
    private readonly LoggerInterface $logger,
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
    $this->termStorage = $entityTypeManager->getStorage('taxonomy_term');
  }

  /**
   * Save the selected hakuvahti filter selections.
   *
   * @param array $filters
   *   The selected filters array: ['filter_name' => ['filter_value_1'...]].
   *
   * @return bool
   *   Saved successfully
   */
  public function saveSelectedFilters(array $filters): bool {
    $now = (new \DateTime())->format('Y-m-d H:i:s');
    // Allows distinguishing between hakuvahti subscriptions.
    $subscription_token = uniqid();

    $query = $this->connection
      ->insert('hakuvahti_selected_filters')
      ->fields(self::FIELDS);

    $has_values = FALSE;
    // Create insert values for query.
    foreach ($filters as $filter_name => $values) {
      foreach ($values as $value) {
        if (!$value) {
          continue;
        }
        $has_values = TRUE;
        $query->values([
          'token' => $subscription_token,
          'filter_name' => $filter_name,
          'filter_value' => substr($value, 0, 254),
          'created_at' => $now,
        ]);
      }
    }

    // No need to execute query if no values.
    if (!$has_values) {
      return TRUE;
    }

    try {
      $query->execute();
    }
    catch (\Exception $e) {
      $this->logger->error("Unable to save hakuvahti filters: {$e->getMessage()}");
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Create the csv data.
   *
   * @param \DateTime|null $from
   *   Datetime filter.
   * @param \DateTime|null $to
   *   Datetime filter.
   * @param string $delimiter
   *   CSV delimiter.
   *
   * @return string
   *   The csv as a string.
   */
  public function createCsvString(?\DateTime $from = NULL, ?\DateTime $to = NULL, string $delimiter = ';'): string {
    try {
      $rows = $this->getSavedFilters($from, $to);
    }
    catch (\Exception $e) {
      $this->logger->error("Unable to fetch filter data: {$e->getMessage()}");
      throw $e;
    }

    if (!$rows) {
      throw new \Exception("No results found");
    }

    return $this->createCsvStringFromArray($rows, $delimiter);
  }

  /**
   * Creates the csv-string.
   *
   * @param array $rows
   *   Data as array.
   * @param string $delimiter
   *   CSV delimiter.
   *
   * @return string
   *   The csv string.
   */
  public function createCsvStringFromArray(array $rows, string $delimiter = ';'): string {
    $filePath = 'php://temp';
    $handle = fopen($filePath, 'w+');

    try {
      // Set csv headers.
      fputcsv($handle, $this::CSV_HEADERS, $delimiter);

      // Add new rows to csv.
      foreach ($rows as $row) {
        fputcsv($handle, (array) $row, $delimiter);
      }

      rewind($handle);
      $csv_data = stream_get_contents($handle);
      fclose($handle);
    }
    catch (\Exception $e) {
      fclose($handle);
      throw $e;
    }

    return $csv_data;
  }

  /**
   * Get the saved filter data.
   *
   * @param \DateTime|null $from
   *   Filter by date.
   * @param \DateTime|null $to
   *   Filter by date.
   *
   * @return array
   *   Rows of saved filter data.
   */
  public function getSavedFilters(?\DateTime $from = NULL, ?\DateTime $to = NULL): array {
    $query = $this->connection
      ->select('hakuvahti_selected_filters', 'f')
      ->fields('f', ['id', 'token', 'filter_name', 'filter_value', 'created_at']);

    if ($from && $to) {
      $from = $from->format('Y-m-d 00:00:00');
      $to = $to->format('Y-m-d 23:59:59');

      $query->condition('f.created_at', $from, '>=')
        ->condition('f.created_at', $to, '<=');
    }

    return $query->execute()
      ->fetchAllAssoc('id');
  }

  /**
   * Get the first entry.
   *
   * Can be used to prefill the start date field in the form.
   *
   * @return \stdClass|null
   *   The first item in database.
   */
  public function getFirstEntry(): \stdClass|null {
    $first_item = $this->connection
      ->select('hakuvahti_selected_filters', 'f')
      ->fields('f', ['id', 'created_at'])
      ->range(0, 1)
      ->execute()
      ->fetchAllAssoc('id');

    return $first_item ? reset($first_item) : NULL;
  }

  /**
   * Parse the elasticsearch query for query parameters.
   *
   * This used to be part of rekry-hakuvahti and was moved to
   * this module in order to make the original implementation
   * more generic.
   * @todo UHF-12318 keyword and related code may be removed.
   *
   * @param string $query
   *   The elasticsearch query.
   * @param string $queryParameters
   *   The url query parameters.
   * @param string $langcode
   *   Which language should be used to translate the value.
   * @param bool $includeKeyword
   *   Include search keyword (for search_description).
   *
   * @return array
   *   Array of selected filters.
   */
  public function parseQuery(string $query, string $queryParameters = '', string $langcode = 'fi', bool $includeKeyword = false): array {
    $elasticQuery = base64_decode($query);
    $queryAsArray = json_decode($elasticQuery, TRUE);
    $data = [];

    // Free text search.
    if (
      $includeKeyword &&
      str_contains($elasticQuery, 'combined_fields') &&
      $combinedFields = $this->sliceTree($queryAsArray['query']['bool']['must'], 'combined_fields')
    ) {
      $keyword = $combinedFields['query'] ?? '';
      $data['vapaa-sana'] = [$keyword];
    }

    $taskAreaField = 'task_area_external_id';
    $task_area_labels = [];
    if (
      str_contains($elasticQuery, $taskAreaField) &&
      $taskAreaIds = $this->sliceTree($queryAsArray['query']['bool']['must'], $taskAreaField)
    ) {
      $task_area_labels = $this->getLabelsByExternalId($taskAreaIds, $langcode);
    }

    $employment_type_labels = [];
    $employmentTypeField = 'employment_type_id';
    if (
      str_contains($elasticQuery, $employmentTypeField) &&
      $employmentIds = $this->sliceTree($queryAsArray['query']['bool']['must'], $employmentTypeField)
    ) {
      $employment_type_labels = $this->getLabelsByTermIds($employmentIds, $langcode);
    }

    $area_filter_labels = [];
    if ($area_filters = $this->extractQueryParameters($queryParameters, 'area_filter')) {
      foreach ($area_filters as $area) {
        $area_filter_labels[] = $this->translateString($area, $langcode);
      }
    }

    $language = $this->sliceTree($queryAsArray['query']['bool']['filter'], '_language');
    $language = empty($language) ? '' : $language;

    // These are used as csv headers, therefore in finnish.
    return $data + [
      'Ammattiala' => $task_area_labels,
      'Palvelussuhteen tyyppi' => $employment_type_labels,
      'Sijainti' => $area_filter_labels,
      'Kieli' => [$language],
    ];
  }

  /**
   * Retrieves taxonomy labels by their taxonomy term IDs in a given language.
   *
   * @param array $term_ids
   *   An array of taxonomy term IDs to load.
   * @param string $language
   *   The language code for the desired translation.
   *
   * @return array
   *   An array of taxonomy term labels in the specified language.
   */
  private function getLabelsByTermIds(array $term_ids, string $language): array {
    $labels = [];
    $terms = $this->termStorage->loadMultiple($term_ids);
    foreach ($terms as $term) {
      assert($term instanceof TermInterface);
      $translated_term = $term->hasTranslation($language) ? $term->getTranslation($language) : $term;
      $labels[] = $translated_term->label();
    }

    return $labels;
  }

  /**
   * Retrieves taxonomy labels by field_external_id values in a given language.
   *
   * @param array $external_ids
   *   An array of external ID values to match.
   * @param string $language
   *   The language code for the desired translation.
   *
   * @return array
   *   An array of taxonomy term labels in the specified language.
   */
  private function getLabelsByExternalId(array $external_ids, string $language): array {
    $labels = [];
    $terms = $this->termStorage->loadByProperties(['field_external_id' => $external_ids]);
    foreach ($terms as $term) {
      assert($term instanceof TermInterface);
      $translated_term = $term->hasTranslation($language) ? $term->getTranslation($language) : $term;
      $labels[] = $translated_term->label();
    }
    return $labels;
  }

  /**
   * Function to extract specific query parameters from a URL string.
   *
   * @param string $url
   *   The URL string to parse.
   * @param string $parameter
   *   The query parameter to extract values for.
   *
   * @return array
   *   An array of values for the specified query parameter.
   */
  private function extractQueryParameters(string $url, string $parameter): array {
    $parsed_url = parse_url($url);
    $query = $parsed_url['query'] ?? '';
    $query_parameters = [];
    $pairs = explode('&', $query);

    foreach ($pairs as $pair) {
      if (empty($pair)) {
        continue;
      }

      // Split the key and value, using + [null, null] to ensure both are set.
      [$key, $value] = explode('=', $pair, 2) + [NULL, NULL];
      if ($key === NULL) {
        continue;
      }

      $key = urldecode($key);
      $value = urldecode($value);

      // If the parameter is the one we're looking for, add it to the array.
      if ($key === $parameter) {
        $query_parameters[] = $value;
      }
    }

    return $query_parameters;
  }

  /**
   * Function to get translated string in a given language.
   *
   * phpcs:ignore is used to mute error about string literals as there
   *   is no other way to do this translation.
   *
   * @param string $string
   *   The string to be translated.
   * @param string $language
   *   The language code for the desired translation.
   *
   * @return string
   *   The translated string.
   */
  private function translateString(string $string, string $language): string {
    $context = fn($context) => ['langcode' => $language, 'context' => "Search filter option: $context"];
    $translatedString = match(TRUE) {
      $string == 'eastern' => $this->t('Eastern area', [], $context('Eastern area')),
      $string == 'central' => $this->t('Central area', [], $context('Central area')),
      $string == 'southern' => $this->t('Southern area', [], $context('Southern area')),
      $string == 'southeastern' => $this->t('South-Eastern area', [], $context('South-Eastern area')),
      $string == 'western' => $this->t('Western area', [], $context('Western area')),
      $string == 'northern' => $this->t('Northern area', [], $context('Northern area')),
      $string == 'northeast' => $this->t('North-Eastern area', [], $context('North-Eastern area')),
      $string == 'No search filters' => $this->t(
        'No search filters', options: ['langcode' => $language, 'context' => 'Hakuvahti empty filters']
      ),
      default => '',
    };

    return (string) $translatedString;
  }

  /**
   * Recursive function to get an array by key from a tree of arrays.
   *
   * @param array $tree
   *   Array we are traversing.
   * @param string $needle
   *   The key we are looking for.
   *
   * @return array|string
   *   The result of the search.
   */
  private function sliceTree(array $tree, string $needle): array|string {
    if (isset($tree[$needle])) {
      return $tree[$needle];
    }

    $result = NULL;
    foreach ($tree as $branch) {
      if (!is_array($branch)) {
        return [];
      }
      if (isset($branch[$needle])) {
        return $branch[$needle];
      }

      $result = $this->sliceTree($branch, $needle);
      if ($result) {
        break;
      }
    }

    return $result ?? [];
  }

}
