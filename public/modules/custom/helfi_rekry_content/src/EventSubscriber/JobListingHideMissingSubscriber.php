<?php

declare(strict_types = 1);

namespace Drupal\helfi_rekry_content\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;
use Drush\Drupal\Migrate\MigrateMissingSourceRowsEvent;
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
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger channel factory.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerChannelFactoryInterface $loggerFactory,
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
    $destinationIDs = $event->getDestinationIds();

    $missingCount = count($destinationIDs);
    if ($missingCount === 0) {
      return;
    }

    $this->loggerFactory->get('helfi_rekry_content')->log(RfcLogLevel::NOTICE,
      $this->formatPlural(
        $missingCount,
        'Total 1 job listing is missing from source and will be checked.',
        'Total @count job listings are missing from source and will be checked.',
        [],
        ['langcode' => 'en']
      ));

    $nodeStorage = $this->entityTypeManager->getStorage('node');
    $unpublishedCount = 0;
    foreach ($destinationIDs as $destinationId) {
      $node = $nodeStorage->load($destinationId['nid']);

      // Unpublish all translations.
      if ($node instanceof NodeInterface && $node->getType() == 'job_listing') {
        foreach (['fi', 'sv', 'en'] as $langcode) {
          // Unpublish the job listing node as it's still published, but it's
          // no longer available at the source.
          if (!$node->hasTranslation($langcode)) {
            continue;
          }

          $translation = $node->getTranslation($langcode);
          if ($translation->isPublished()) {
            $translation->setUnpublished();
            if ($translation->hasField('publish_on') && !empty($translation->get('publish_on')->getValue())) {
              // Also clear the publish on date to make sure the translation is
              // not going to be re-published.
              $translation->set('publish_on', NULL);
            }
            $translation->save();
            $unpublishedCount++;
          }
        }
      }
    }

    $this->loggerFactory->get('helfi_rekry_content')->log(RfcLogLevel::NOTICE,
      $this->formatPlural(
        $unpublishedCount,
        '1 missing item was published and is now unpublished.',
        '@count missing items were published and are now unpublished.',
        [],
        ['langcode' => 'en']
      ));
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
