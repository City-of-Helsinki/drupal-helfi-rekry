<?php

declare(strict_types=1);

namespace Drupal\helfi_google_api\EventSubscriber;

use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\helfi_google_api\JobIndexingService;
use Drupal\helfi_rekry_content\Entity\JobListing;
use Drupal\scheduler\SchedulerEvent;
use Drupal\scheduler\SchedulerEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * {@inheritdoc}
 */
class JobPublishStateSubscriber implements EventSubscriberInterface {

  public function __construct(
    private readonly JobIndexingService $jobIndexingService,
    private readonly LoggerInterface $logger,
    private readonly UrlGeneratorInterface $urlGenerator,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
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

    $this->jobIndexingService->indexEntity($entity);
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

    $this->jobIndexingService->deindexEntity($entity);
  }

}
