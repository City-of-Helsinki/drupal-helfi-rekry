<?php

declare(strict_types=1);

namespace Drupal\helfi_hakuvahti;

use Drupal\Core\Database\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Save hakuvahti filter selections to database.
 */
readonly class HakuvahtiTracker {

  /**
   * Csv header fields which matches the database fields.
   */
  private const FIELDS = ['token', 'filter_name', 'filter_value', 'created_at'];

  /**
   * The csv headers.
   */
  private const CSV_HEADERS = ['id', 'tunniste', 'suodatin', 'valittu arvo', 'luontiaika'];

  /**
   * The constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The connection.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */
  public function __construct(
    private readonly Connection $connection,
    #[Autowire(service: 'logger.channel.helfi_hakuvahti')]
    private readonly LoggerInterface $logger,
  ) {
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

    // Create insert values for query.
    foreach ($filters as $filter_name => $values) {
      foreach ($values as $value) {
        $query->values([
          'token' => $subscription_token,
          'filter_name' => $filter_name,
          'filter_value' => $value,
          'created_at' => $now,
        ]);
      }
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
   *
   * @return string
   *   The csv as a string.
   */
  public function createCsvString(?\DateTime $from = NULL, ?\DateTime $to = NULL): string {
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

    return $this->createCsvStringFromArray($rows);
  }

  /**
   * Creates the csv-string.
   *
   * @param array $rows
   *   Data as array.
   *
   * @return string
   *   The csv string.
   */
  public function createCsvStringFromArray(array $rows): string {
    $delimiter = ',';
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

}
