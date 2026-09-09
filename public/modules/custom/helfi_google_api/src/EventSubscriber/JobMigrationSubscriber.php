<?php

declare(strict_types=1);

namespace Drupal\helfi_google_api\EventSubscriber;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\PagerSelectExtender;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\helfi_google_api\JobIndexingService;
use Drupal\helfi_rekry_content\Entity\JobListing;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Send indexing request ASAP after migration is done if necessary.
 */
class JobMigrationSubscriber implements EventSubscriberInterface {

  /**
   * The constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Queue\QueueFactory $queueFactory
   *   The queue factory.
   * @param \Drupal\Core\Database\Connection $database
   *   The connection.
   * @param \Drupal\helfi_google_api\JobIndexingService $jobIndexingService
   *   The job indexing service.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly QueueFactory $queueFactory,
    private readonly Connection $database,
    private readonly JobIndexingService $jobIndexingService,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      MigrateEvents::POST_ROW_SAVE => 'postRowSave',
    ];
  }

  /**
   * Send indexing request to google.
   *
   * @param \Drupal\migrate\Event\MigratePostRowSaveEvent $event
   *   The scheduler event.
   */
  public function postRowSave(MigratePostRowSaveEvent $event): void {
    $id = $event->getDestinationIdValues();
    $id = reset($id);

    $node = $this->entityTypeManager
      ->getStorage('node')
      ->load($id);

    if (
      !$node instanceof JobListing ||
      !$this->shouldRequestIndexingImmediately($node)
    ) {
      return;
    }

    // Prevent duplicate queue entries.
    $results = $this->database
      ->select('queue')
      ->extend(PagerSelectExtender::class)
      ->fields('queue', [
        'name',
        'data',
      ])
      ->condition('name', 'job_listing_indexing_request')
      ->condition('data', '%"' . $id . '"%', 'LIKE')
      ->limit(1)
      ->execute()
      ->fetchAll();

    if (is_array($results) && count($results) > 0) {
      return;
    }

    // The jobs are edited quite often,
    // let's not reindex a page if it's only edited.
    $temp_redirect_exists = $this->jobIndexingService->hasTemporaryRedirect(
      $node,
      $node->language()->getId()
    );
    if ($temp_redirect_exists) {
      return;
    }

    $this->queueFactory->get('job_listing_indexing_request')
      ->createItem(['nid' => $node->id()]);
  }

  /**
   * Do we need to send indexing request right away ?
   *
   * @param \Drupal\helfi_rekry_content\Entity\JobListing $node
   *   The job listing node.
   *
   * @return bool
   *   Node should be indexed right now.
   */
  private function shouldRequestIndexingImmediately(JobListing $node): bool {
    return $node->isPublished() || (!$node->get('publish_on')->isEmpty() && $node->isPublished());
  }

}
