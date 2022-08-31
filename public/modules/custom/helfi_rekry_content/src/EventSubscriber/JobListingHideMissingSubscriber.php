<?php

declare(strict_types = 1);

namespace Drupal\helfi_rekry_content\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\node\NodeInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribe to job listing import events for hiding missing items.
 */
class JobListingHideMissingSubscriber implements EventSubscriberInterface {

  /**
   * Constructs a new JobListingHideMissingSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      MigrateEvents::PRE_IMPORT => 'hideMissingJobListings',
    ];
  }

  /**
   * Unpublish job listings that are no longer available at the API.
   *
   * @param \Drupal\migrate\Event\MigrateImportEvent $event
   *   The migration import event.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function hideMissingJobListings(MigrateImportEvent $event): void {
    // Return early if the migration is not a job listing migration.
    $migrationId = $event->getMigration()->id();
    if (!in_array($migrationId, $this->getJobListingMigrations())) {
      return;
    }

    $nodeStorage = $this->entityTypeManager->getStorage('node');

    /** @var \Drupal\migrate\Plugin\migrate\id_map\Sql $idMap */
    $idMap = $event->getMigration()->getIdMap();
    // Mark all previously imported as ready to be re-imported in order to have
    // a full list of source IDs.
    $idMap->prepareUpdate();

    /** @var \Drupal\migrate_plus\Plugin\migrate\source\Url $source */
    $source = clone $event->getMigration()->getSourcePlugin();
    $source->rewind();
    $sourceIdValues = [];

    // Get source IDs from the current source.
    while ($source->valid()) {
      $sourceIdValues[] = $source->current()->getSourceIdValues();
      $source->next();
    }

    // Iterate existing migration rows.
    $idMap->rewind();
    while ($idMap->valid()) {
      // Get current source ID and ID for the existing node.
      $mapSourceId = $idMap->currentSource();
      $destinationIds = $idMap->currentDestination();

      if (!in_array($mapSourceId, $sourceIdValues, TRUE) && !empty($destinationIds['nid'])) {
        // The job listing row is no longer found from source.
        $node = $nodeStorage->load($destinationIds['nid']);

        // Use node translation if available.
        $migrationLangcode = $this->getMigrationLangcode($migrationId);
        if ($node->hasTranslation($migrationLangcode)) {
          $node = $node->getTranslation($migrationLangcode);
        }

        if ($node instanceof NodeInterface && $node->getType() == 'job_listing' && $node->isPublished()) {
          // Unpublish the job listing node as it's still published, but its
          // source is no longer available.
          $node->setUnpublished();
          if ($node->hasField('publish_on') && !empty($node->get('publish_on')->getValue())) {
            // Also clear the publish on date to make sure the node is not
            // going to be published anymore.
            $node->set('publish_on', NULL);
          }
          $node->save();
        }
      }

      $idMap->next();
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
