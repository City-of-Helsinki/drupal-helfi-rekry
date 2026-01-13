<?php

declare(strict_types=1);

namespace Drupal\helfi_rekry_content\EventSubscriber;

use Drupal\helfi_hakuvahti\Event\SubscriptionAlterEvent;
use Drupal\helfi_hakuvahti\Event\SubscriptionEvent;
use Drupal\helfi_hakuvahti\HakuvahtiRequest;
use Drupal\helfi_rekry_content\Service\HakuvahtiTracker;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * {@inheritdoc}
 */
class HakuvahtiSubscriptionSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      SubscriptionAlterEvent::class => 'hakuvahtiAlterSubscription',
      SubscriptionEvent::class => 'hakuvahtiSubscriptionActions',
    ];
  }

  public function __construct(private HakuvahtiTracker $hakuvahtiTracker) {
  }

  /**
   * Alter hakuvahti subscription before sending the request.
   *
   * This computes the searchDescription field for the request. We don't
   * want the user to have control over any text on the email body, so the
   * search description is built on the backend.
   */
  public function hakuvahtiAlterSubscription(SubscriptionAlterEvent $event): void {
    $request = $event->getHakuvahtiRequest();
    $filters = $this->hakuvahtiTracker->parseQuery($request->elasticQuery, includeKeyword: TRUE);
    $data = $request->getServiceRequestData();

    // Extract free search term if present, limit to 10 characters.
    $freeSearchTerm = $filters['vapaa-sana'][0] ?? '';
    unset($filters['vapaa-sana']);
    if (mb_strlen($freeSearchTerm) > 10) {
      $freeSearchTerm = mb_substr($freeSearchTerm, 0, 10) . '...';
    }

    $parts = [];
    if ($freeSearchTerm) {
      $parts[] = $freeSearchTerm;
    }

    foreach ($filters as $items) {
      foreach ($items as $item) {
        if ($item) {
          $parts[] = $item;
        }
      }
    }

    $event->setHakuvahtiRequest(new HakuvahtiRequest(array_merge($data, [
      'search_description' => implode(', ', $parts),
    ])));
  }

  /**
   * Save the selected filters to database.
   */
  public function hakuvahtiSubscriptionActions(SubscriptionEvent $event): void {
    $filters = $this->hakuvahtiTracker->parseQuery($event->getQuery(), $event->getQueryParameters());
    $this->hakuvahtiTracker->saveSelectedFilters($filters);
  }

}
