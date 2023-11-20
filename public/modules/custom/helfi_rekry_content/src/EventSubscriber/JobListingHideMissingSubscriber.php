<?php

declare(strict_types = 1);

namespace Drupal\helfi_rekry_content\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drush\Drupal\Migrate\MigrateMissingSourceRowsEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribe to job listing import events for hiding missing items.
 */
class JobListingHideMissingSubscriber implements EventSubscriberInterface {
  use StringTranslationTrait;

  /**
   * Constructs a new JobListingHideMissingSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger channel.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      MigrateMissingSourceRowsEvent::class => 'onMissingSourceRows',
    ];
  }

  /**
   * Reacts on detecting a list of missing source rows after an import.
   *
   * Job listings that are missing from source but are still published, will be
   * unpublished.
   *
   * @param \Drush\Drupal\Migrate\MigrateMissingSourceRowsEvent $event
   *   The missing source rows event.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function onMissingSourceRows(MigrateMissingSourceRowsEvent $event): void {
    // Return early if the migration is not a job listing migration.
    $migrationId = $event->getMigration()->id();
    if (!in_array($migrationId, $this->getJobListingMigrations())) {
      return;
    }

    // Get destination ids for job listings that are missing from source.
    $destinationIds = array_reduce(
      $event->getDestinationIds(),
      function ($values, $destinationValue) {
        if (isset($destinationValue['nid'])) {
          $value = $destinationValue['nid'];
          $values[$value] = $value;
        }

        return $values;
      },
      []
    );

    $missingCount = count($destinationIds);
    if ($missingCount === 0) {
      return;
    }

    // Query missing nodes that are still published.
    $query = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->condition('nid', $destinationIds, 'IN')
      ->condition('status', 1)
      ->notExists('unpublish_on');
    $nids = $query->execute();

    foreach ($nids as $nid) {
      $job = ['nid' => $nid];
      \Drupal::queue('job_listing_unpublish_worker')->createItem($job);
    }
  }

  /**
   * Return job listing migration names.
   *
   * The corresponding migration source must include all items for the given
   * migration.
   *
   * @return array
   *   The migration names.
   */
  protected function getJobListingMigrations(): array {
    return [
      'helfi_rekry_jobs:all',
      'helfi_rekry_jobs:all_en',
      'helfi_rekry_jobs:all_sv',
    ];
  }

  /**
   * Get langcode from language specific migration ID.
   *
   * @param string $migrationId
   *   The language specific migration ID.
   *
   * @return string
   *   The langcode.
   */
  protected function getMigrationLangcode(string $migrationId): string {
    if (str_contains($migrationId, '_sv')) {
      return 'sv';
    }
    elseif (str_contains($migrationId, '_en')) {
      return 'en';
    }
    return 'fi';
  }

}
