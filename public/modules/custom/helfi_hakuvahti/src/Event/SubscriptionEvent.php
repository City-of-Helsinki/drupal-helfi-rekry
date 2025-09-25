<?php

declare(strict_types=1);

namespace Drupal\helfi_hakuvahti\Event;

use Drupal\Component\EventDispatcher\Event;

/**
 * Event which is dispatched when hakuvahti is created successfully.
 */
final class SubscriptionEvent extends Event {

  const EVENT_NAME = 'hakuvahti.subscribe';

  /**
   * The elasticsearch query.
   */
  private string $query;

  /**
   * The constructor.
   */
  public function __construct(string $query) {
    $this->query = $query;
  }

  /**
   * Get the elasticsearch query.
   *
   * @return string
   *   The elasticsearch query.
   */
  public function getQuery(): string {
    return $this->query;
  }

}
