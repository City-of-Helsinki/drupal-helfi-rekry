<?php

declare(strict_types=1);

namespace Drupal\helfi_hakuvahti\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Creates new subscription.
 */
final class HelfiHakuvahtiSubscribeController extends ControllerBase {

  private EntityStorageInterface $termStorage;

  /**
   * Constructor for the HelfiHakuvahtiSubscribeController class.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   */
  public function __construct(
    protected RequestStack $requestStack,
    private readonly ClientInterface $client,
    #[Autowire(service: 'logger.channel.helfi_hakuvahti')] private readonly LoggerInterface $logger,
  ) {
    $this->termStorage = $this->entityTypeManager()->getStorage('taxonomy_term');
  }

  /**
   * A method to handle the POST request for subscription.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response based on the subscription request.
   */
  public function post(): JsonResponse {
    if (!$hakuvahtiServer = getenv('HAKUVAHTI_URL')) {
      $this->logger->error('Hakuvahti is missing a required HAKUVAHTI_URL configuration.');
      return new JsonResponse(['success' => FALSE, 'error' => 'Unable to process the request.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    $request = $this->requestStack->getCurrentRequest();
    $body = $request->getContent(FALSE);
    $bodyObj = json_decode($body);
    $bodyObj->search_description = $this->getSearchDescriptionTaxonomies($bodyObj);

    $token = $request->headers->get('token');

    // @todo Validate token.
    //
    // Somehow, we would need to validate token from
    // /session/token from react
    // side, but there's just no way to match it at backend?!
    // $csrfTokenService = $this->container->get('csrf_token');
    // $expectedToken = $csrfTokenService->get('session');
    // if ($this->csrfTokenService->validate($token, 'session') === FALSE) {}.
    try {
      $this->client->request('POST', "$hakuvahtiServer/subscription", [
        RequestOptions::JSON => $bodyObj,
        RequestOptions::HEADERS => [
          'token' => $token,
          'Content-Type' => 'application/json',
        ],
      ]);
    }
    catch (GuzzleException $e) {
      $this->logger->error("Unable to send Hakuvahti-request - Code {$e->getCode()}: {$e->getMessage()}");
      return new JsonResponse(['success' => FALSE, 'error' => 'Error while handling the request.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    return new JsonResponse(['success' => TRUE], Response::HTTP_OK);
  }

  /**
   * Retrieves search description taxonomies from the provided object.
   *
   * @param mixed $obj
   *   The object containing elastic query data.
   *
   * @return string
   *   The concatenated search description taxonomies.
   */
  private function getSearchDescriptionTaxonomies(mixed $obj): string {
    $terms = [];
    $employmentTermLabels = [];
    $areaFiltersTranslated = [];
    $query = '';

    $elasticQuery = base64_decode($obj->elastic_query);
    $queryAsArray = json_decode($elasticQuery, TRUE);

    // Free text search.
    if (
      $this->elasticQueryContains('combined_fields', $elasticQuery) &&
      $combinedFields = $this->sliceTree($queryAsArray['query']['bool']['must'], 'combined_fields')
    ) {
      $query = $combinedFields['query'];
    }

    $taskAreaField = 'task_area_external_id';
    if (
      $this->elasticQueryContains($taskAreaField, $elasticQuery) &&
      $taskAreaIds = $this->sliceTree($queryAsArray['query']['bool']['must'], $taskAreaField)
    ) {
      $terms = $this->getLabelsByExternalId($taskAreaIds, $obj->lang);
    }

    $employmentTypeField = 'employment_type_id';
    if (
      $this->elasticQueryContains($employmentTypeField, $elasticQuery) &&
      $employmentIds = $this->sliceTree($queryAsArray['query']['bool']['must'], $employmentTypeField)
    ) {
      $employmentTermLabels = $this->getLabelsByTermIds($employmentIds, $obj->lang);
    }

    // Job location:
    if ($area_filters = $this->extractQueryParameters($obj->query, 'area_filter')) {
      foreach ($area_filters as $area) {
        $areaFiltersTranslated[] = $this->translateAreaString($area, $obj->lang);
      }
    }

    $description = $this->buildDescription($query, $terms, $areaFiltersTranslated, $employmentTermLabels);

    return $description ? $description : '*';
  }

  /**
   * Check if the non-decoded query contains a specific string.
   *
   * @param string $term_name
   *   What are we looking for.
   * @param string $query_string
   *   Where are we looking from.
   *
   * @return bool
   *   The string exists.
   */
  private function elasticQueryContains(string $term_name, string $query_string): bool {
    return str_contains($query_string, $term_name);
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
      $translated_term = $term->hasTranslation($language) ? $term->getTranslation($language) : $term;
      $labels[] = $translated_term->label();
    }
    return $labels;
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
   * Build description string out of search parameters.
   *
   * @param string $query
   *   The search query from text field.
   * @param array $terms
   *   Array of term values.
   * @param array $areaFiltersTranslated
   *   Array of area labels.
   * @param array $employmentTermLabels
   *   Array of employment terms.
   *
   * @return string
   *   The description string.
   */
  private function buildDescription(string $query, array $terms, array $areaFiltersTranslated, array $employmentTermLabels): string {
    $description = $query;
    $allTerms = array_merge($terms, $areaFiltersTranslated);

    $description .= $allTerms ? ', ' : '';
    $description .= implode(', ', array_filter($allTerms));

    // Employment label should use / instead of comma.
    $description .= $employmentTermLabels ? ', ': '';
    $description .= implode(' / ', $employmentTermLabels);

    return $description;
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
  private function translateAreaString(string $area, string $language): string {
    $translatedString = match(true) {
      $area == 'eastern' => $this->t('Eastern area', [], ['langcode' => $language, 'context' => 'Search filter option: Eastern area']),
      $area == 'central' => $this->t('Central area', [], ['langcode' => $language, 'context' => 'Search filter option: Central area']),
      $area == 'southern' => $this->t('Southern area', [], ['langcode' => $language, 'context' => 'Search filter option: Southern area']),
      $area == 'southeastern' => $this->t('South-Eastern area', [], ['langcode' => $language, 'context' => 'Search filter option: South-Eastern area']),
      $area == 'western' => $this->t('Western area', [], ['langcode' => $language, 'context' => 'Search filter option: Western area']),
      $area == 'northern' => $this->t('Northern area', [], ['langcode' => $language, 'context' => 'Search filter option: Northern area']),
      $area == 'northeast' => $this->t('North-Eastern area', [], ['langcode' => $language, 'context' => 'Search filter option: North-Eastern area']),
      default => '',
    };

    return (string) $translatedString;
  }

  /**
   * Recursive function to get an array by key from a tree of arrays.
   *
   * @param mixed $tree
   *   Array we are traversing.
   * @param string $needle
   *   The key we are looking for.
   *
   * @return false|array
   *   False or the array we are looking for.
   */
  private function sliceTree(array $tree, string $needle): array {
    if (is_array($tree) && isset($tree[$needle])) return $tree[$needle];

    $result = NULL;
    foreach($tree as $branch) {
      if (!is_array($branch)) return [];
      if (isset($branch[$needle])) return $branch[$needle];

      $result = $this->sliceTree($branch, $needle);
      if ($result) {
        break;
      }
    }

    return $result ?? [];
  }

}
