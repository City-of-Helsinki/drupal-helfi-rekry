<?php

declare(strict_types=1);

namespace Drupal\helfi_rekry_content\EventSubscriber;

use Drupal\helfi_hakuvahti\Event\SubscriptionEvent;
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
    return [SubscriptionEvent::EVENT_NAME => 'hakuvahtiSubscriptionActions'];
  }

  public function __construct(private HakuvahtiTracker $hakuvahtiTracker) {
  }

  /**
   * Save the selected filters to database.
   */
  public function hakuvahtiSubscriptionActions(SubscriptionEvent $event): void {
    $filters = $this->hakuvahtiTracker->parseQuery($event->getQuery());
    $this->hakuvahtiTracker->saveSelectedFilters($filters);
  }

}
