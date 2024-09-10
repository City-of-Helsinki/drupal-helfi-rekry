<?php

declare(strict_types=1);

namespace Drupal\helfi_google_api\EventSubscriber;

use Drupal\helfi_api_base\Environment\EnvironmentEnum;
use Drupal\helfi_api_base\Environment\EnvironmentResolverInterface;
use Drupal\helfi_google_api\JobIndexingService;
use Drupal\helfi_rekry_content\Entity\JobListing;
use Drupal\scheduler\SchedulerEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * {@inheritdoc}
 */
class JobPublishStateSubscriber implements EventSubscriberInterface {

  /**
   * The constructor.
   *
   * @param \Drupal\helfi_google_api\JobIndexingService $jobIndexingService
   *   The job indexing service.
   * @param Drupal\helfi_api_base\Environment\EnvironmentResolverInterface $environmentResolver
   *   The environment resolver.
   */
  public function __construct(
    private readonly JobIndexingService $jobIndexingService,
    private readonly EnvironmentResolverInterface $environmentResolver,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // @todo Enable after feature tested in production.
    /*
    return [
    SchedulerEvents::PUBLISH => 'sendIndexingRequest',
    SchedulerEvents::PUBLISH_IMMEDIATELY => 'sendIndexRequest',
    SchedulerEvents::UNPUBLISH => 'sendDeindexingRequest',
    ];
     */
    return [];
  }

  /**
   * Send indexing request to google.
   *
   * @param \Drupal\scheduler\SchedulerEvent $event
   *   The scheduler event.
   */
  public function sendIndexingRequest(SchedulerEvent $event): void {
    if (!$this->isProduction()) {
      return;
    }

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
    if (!$this->isProduction()) {
      return;
    }

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

  /**
   * Check if in production.
   *
   * @return bool
   *   Is production.
   */
  private function isProduction(): bool {
    return $this->environmentResolver->getActiveEnvironment()->getEnvironment() === EnvironmentEnum::Prod;
  }

}
