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

  public function sendIndexingRequest(SchedulerEvent $event) {
    $entity = $event->getNode();
    if (!$entity instanceof JobListing) {
      return;
    }

    $langcodes = ['fi', 'en', 'sv'];
    $results = [];

    foreach($langcodes as $langcode) {
      try {
        $hasRedirect = $this->jobIndexingService->temporaryRedirectExists($entity, $langcode);
        if ($hasRedirect) {
          // log, continue.
          continue;
        }
      }
      catch (\Exception $e) {
        // Log.
        continue;
      }

      try {
        $indexing_url = $this->jobIndexingService->createTemporaryRedirectUrl($entity, $langcode);
      }
      catch (\Exception $e) {
        // cannot create url, log
        continue;
      }

      $results[] = $indexing_url;
    }

    $result = $this->jobIndexingService->indexItems($results);

    if ($result['errors']) {
      // Some of the urls failed
      // log
    }

  }

  /**
   * Send deindexing request to google.
   *
   * @param SchedulerEvent $event
   *   The scheduler event.
   */
  public function sendDeindexingRequest(SchedulerEvent $event) {
    $entity = $event->getNode();
    $langcode = $entity->language()->getId();
    if (!$entity instanceof JobListing) {
      return;
    }

    $redirect = $this->jobIndexingService->getExistingTemporaryRedirect($entity, $langcode);
    if (!$redirect) {
      return;
    }

    $base_url = $this->urlGenerator->generateFromRoute(
      '<front>',
      [],
      ['absolute' => TRUE,'language' => $entity->language()]
    );

    $url_to_deindex = $base_url . $redirect->getSourceUrl();

    $this->jobIndexingService->deindexItems([$url_to_deindex]);
  }

}
