<?php

declare(strict_types=1);

namespace Drupal\helfi_rekry_content\Service;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Utility\Error;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\helfi_rekry_content\Entity\JobListing;
use Drupal\helfi_rekry_content\Helbit\HelbitClient;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Service for removing expired job listings.
 */
final class JobListingCleaner implements LoggerAwareInterface {

  use LoggerAwareTrait;

  /**
   * Value used to determined if a listing is considered expired.
   */
  private const string EXPIRE_THRESHOLD = '-6 months';

  /**
   * Maximum number of job listings that are cleaned in a single operation.
   */
  private const int BATCH_SIZE = 100;

  /**
   * The migration ID for job listings.
   */
  private const string MIGRATION_ID = 'helfi_rekry_jobs';

  /**
   * Known job listings.
   *
   * @var array<string, array<string, bool>>
   */
  private static array $jobListingCache = [];

  public function __construct(
    private readonly HelbitClient $client,
    private readonly MigrationPluginManagerInterface $migrationPluginManager,
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
  }

  /**
   * Clean expired job listings.
   *
   * @return int
   *   Number of entities deleted.
   */
  public function deleteExpired(): int {
    $count = 0;

    $ids = $this->findExpiredJobListings();
    $jobListings = $this->entityTypeManager->getStorage('node')
      ->loadMultiple($ids);

    $idMap = $this->getMigrationIdMap();

    try {
      $this->fetchHelbitJobListings();
    }
    catch (\Exception $e) {
      // Stop if the job fetching fails.
      Error::logException($this->logger, $e);
      return 0;
    }

    // It's not safe to delete job listings if the API return no job listings.
    $skipLanguage = [];
    foreach (['fi', 'en', 'sv'] as $langcode) {
      if (!isset(self::$jobListingCache[$langcode]) || empty(self::$jobListingCache[$langcode])) {
        $this->logger->alert("Helbit returned no job listings for language $langcode. Skipping the $langcode cleanup.");
        if (!in_array($langcode, $skipLanguage)) {
          $skipLanguage[] = $langcode;
        }
      }
    }

    foreach ($jobListings as $jobListing) {
      assert($jobListing instanceof JobListing);
      $recruitmentId = $jobListing->getRecruitmentId();
      $langcode = $jobListing->language()->getId();

      if (in_array($langcode, $skipLanguage)) {
        continue;
      }

      // The job listing should be deleted if it is not present in the API.
      if (!isset(self::$jobListingCache[$langcode][$recruitmentId])) {
        foreach ($jobListing->getTranslationLanguages() as $language) {
          // Clean up the migration map entry.
          $idMap?->delete([$recruitmentId, $language->getId()]);
        }

        $jobListing->delete();
        $count += 1;
      }
    }

    return $count;
  }

  /**
   * Get the ID map for the job listings migration.
   *
   * @return \Drupal\migrate\Plugin\MigrateIdMapInterface|null
   *   The migration ID map, or NULL if the migration could not be loaded.
   */
  private function getMigrationIdMap(): ?MigrateIdMapInterface {
    try {
      $migration = $this->migrationPluginManager->createInstance(self::MIGRATION_ID);
      assert($migration instanceof MigrationInterface);
      return $migration->getIdMap();
    }
    catch (\Exception) {
      return NULL;
    }
  }

  /**
   * Query for expired job listings from the database.
   *
   * @return array<string|int>
   *   Job listings entity ids.
   */
  private function findExpiredJobListings(): array {
    $query = $this->entityTypeManager->getStorage('node')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'job_listing')
      // Only consider unpublished.
      ->condition('status', 0);

    $legacy = $query->andConditionGroup()
      // Delete legacy listings that do not have all the required fields.
      ->condition('field_publication_ends', NULL, 'IS NULL')
      ->condition('changed', strtotime('-1 year'), '<');

    $thresholdOrLegacy = $query->orConditionGroup()
      // Entities that have been unpublished before the threshold.
      ->condition('field_publication_ends', $this->getExpiredThreshold(), '<')
      ->condition($legacy);

    return $query
      ->condition($thresholdOrLegacy)
      ->range(0, JobListingCleaner::BATCH_SIZE)
      ->sort('field_publication_ends', 'ASC')
      ->execute();
  }

  /**
   * Fetch and cache existing job listings by language.
   */
  private function fetchHelbitJobListings(): void {
    foreach (['fi', 'en', 'sv'] as $langcode) {
      $results = $this->client->getJobListings($langcode);

      foreach ($results as $result) {
        $id = $result['jobAdvertisement']['id'];
        if ($id) {
          self::$jobListingCache[$langcode][$id] = TRUE;
        }
      }
    }
  }

  /**
   * Return storage formatted timestamp.
   *
   * Job listings that are unpublished after this time are considered expired.
   *
   * @return string
   *   Formatted string that can be used in a query.
   */
  private function getExpiredThreshold(): string {
    $expiredThreshold = new DrupalDateTime(JobListingCleaner::EXPIRE_THRESHOLD);
    $expiredThreshold->setTimezone(new \DateTimezone(DateTimeItemInterface::STORAGE_TIMEZONE));

    return $expiredThreshold->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
  }

}
