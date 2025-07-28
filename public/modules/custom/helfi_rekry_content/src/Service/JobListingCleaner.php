<?php

declare(strict_types=1);

namespace Drupal\helfi_rekry_content\Service;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\helfi_rekry_content\Helbit\HelbitClient;
use Webmozart\Assert\Assert;

/**
 * Service for removing expired job listings.
 */
final class JobListingCleaner {

  /**
   * Value used to determined if a listing is considered expired.
   */
  private const EXPIRE_THRESHOLD = '-1 week';

  /**
   * Maximum number of job listings that are cleaned in a single operation.
   */
  private const BATCH_SIZE = 100;

  /**
   * Job listing storage.
   */
  private readonly EntityStorageInterface $storage;

  /**
   * Known job listings.
   *
   * @var array
   */
  private static array $jobListingCache = [];

  /**
   * Constructs a JobListingCleaner object.
   */
  public function __construct(
    private readonly HelbitClient $client,
    EntityTypeManagerInterface $entityTypeManager,
  ) {
    $this->storage = $entityTypeManager->getStorage('node');
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
    $jobListings = $this->storage->loadMultiple($ids);

    foreach ($jobListings as $jobListing) {
      assert($jobListing instanceof FieldableEntityInterface);

      // The job listing should be deleted if it is not present in the API.
      if (!$this->isJobListingInHelbit($jobListing)) {
        $jobListing->delete();
        $count += 1;
      }
    }

    return $count;
  }

  /**
   * Check if the given job listing is removed from Helbit.
   *
   * If the listing is not removed, it should not be deleted from Drupal.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $jobListing
   *   Job listing entity.
   *
   * @return bool
   *   TRUE if job listing is removed from Helbit API.
   */
  private function isJobListingInHelbit(FieldableEntityInterface $jobListing): bool {
    Assert::true($jobListing->hasField('field_recruitment_id'));

    $language = $jobListing->language();
    $langcode = $language->getId();

    if (empty(self::$jobListingCache[$langcode])) {
      if (empty($results = $this->client->getJobListings($langcode))) {
        // API error, we don't know if this entity is deleted.
        return FALSE;
      }

      // Collect job listing ids into static variable.
      foreach ($results as $result) {
        if ($id = $result['jobAdvertisement']['id'] ?? FALSE) {
          self::$jobListingCache[$langcode][$id] = TRUE;
        }
      }
    }

    $id = $jobListing->get('field_recruitment_id')->getString();

    return self::$jobListingCache[$langcode][$id] ?? FALSE;
  }

  /**
   * Query for expired job listings from the database.
   *
   * @return array
   *   Job listings entity ids.
   */
  private function findExpiredJobListings(): array {
    $query = $this->storage
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
