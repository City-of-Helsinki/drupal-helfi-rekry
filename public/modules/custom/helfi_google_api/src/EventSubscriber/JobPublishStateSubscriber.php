<?php

declare(strict_types=1);

namespace Drupal\helfi_google_api\EventSubscriber;

use Drupal\helfi_google_api\JobIndexingService;
use Drupal\helfi_rekry_content\Entity\JobListing;
use Drupal\scheduler\SchedulerEvent;
use Drupal\scheduler\SchedulerEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribe to job publishing events.
 */
class JobPublishStateSubscriber implements EventSubscriberInterface {

  /**
   * The constructor.
   *
   * @param \Drupal\helfi_google_api\JobIndexingService $jobIndexingService
   *   The job indexing service.
   */
  public function __construct(
    private readonly JobIndexingService $jobIndexingService,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      SchedulerEvents::PUBLISH => 'sendIndexingRequest',
      SchedulerEvents::PUBLISH_IMMEDIATELY => 'sendIndexRequest',
      SchedulerEvents::UNPUBLISH => 'sendDeindexingRequest',
    ];
  }

  /**
   * Send indexing request to google.
   *
   * @param \Drupal\scheduler\SchedulerEvent $event
   *   The scheduler event.
   */
  public function sendIndexingRequest(SchedulerEvent $event): void {
    $entity = $event->getNode();
    if (!$entity instanceof JobListing) {
      return;
    }

    try {
      $this->jobIndexingService->indexEntity($entity);
    }
    catch (\Exception $exception) {
      // Has been logged by indexing service.
    }
  }

  /**
   * Send deindexing request to google.
   *
   * @param \Drupal\scheduler\SchedulerEvent $event
   *   The scheduler event.
   */
  public function sendDeindexingRequest(SchedulerEvent $event): void {
    $entity = $event->getNode();
    if (!$entity instanceof JobListing) {
      return;
    }

    try {
      $this->jobIndexingService->deindexEntity($entity);
    }
    catch (\Exception) {
      // Has been logged by indexing service.
    }
  }

}
