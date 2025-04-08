<?php

declare(strict_types=1);

namespace Drupal\helfi_rekry_content\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;
use Drush\Drupal\Migrate\MigrateMissingSourceRowsEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
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
   * @param \Drupal\Core\Queue\QueueFactory $queueFactory
   *   The queue factory.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    #[Autowire(service: 'logger.channel.helfi_rekry_content')]
    protected LoggerInterface $logger,
    protected QueueFactory $queueFactory,
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

    $query = $this->entityTypeManager
      ->getStorage('node')
      ->getQuery();

    // Query missing nodes that are still published
    // or still scheduled to be published.
    $orCondition = $query->orConditionGroup();
    $orCondition->condition('status', NodeInterface::PUBLISHED);
    $orCondition->exists('publish_on');

    $nids = $query
      ->accessCheck(FALSE)
      ->condition('nid', $destinationIds, 'IN')
      ->condition($orCondition)
      ->execute();

    foreach ($nids as $nid) {
      $job = ['nid' => $nid];
      $this->queueFactory->get('job_listing_unpublish_worker')->createItem($job);
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
      'helfi_rekry_jobs',
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
