<?php

declare(strict_types=1);

namespace Drupal\helfi_rekry_content\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\helfi_google_api\JobIndexingService;
use Drupal\helfi_rekry_content\Entity\JobListing;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Index the migrated job listings which are published on migrate.
 *
 * @QueueWorker(
 *   id = "job_listing_indexing_request",
 *   title = @Translation("Job listing unpublish worker"),
 *   cron = {"time" = 60}
 * )
 */
final class IndexingWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a new UnpublishWorker object.
   *
   * @param array $configuration
   *   Configuration array.
   * @param mixed $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\helfi_google_api\JobIndexingService $jobIndexingService
   *   The job indexing service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected JobIndexingService $jobIndexingService,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) : self {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('Drupal\helfi_google_api\JobIndexingService'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data): void {
    if (!$data['nid']) {
      return;
    }

    $nodeStorage = $this->entityTypeManager->getStorage('node');
    $node = $nodeStorage->load($data['nid']);

    if (!$node instanceof JobListing) {
      return;
    }

    try {
      $this->jobIndexingService->indexEntity($node);
    }
    catch (\Exception $e) {
      // Handled in service.
    }
  }

}
