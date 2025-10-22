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
   * The url query parameters.
   */
  private string $queryParameters;

  /**
   * The constructor.
   */
  public function __construct(string $query, string $queryParameters) {
    $this->query = $query;
    $this->queryParameters = $queryParameters;
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

  /**
   * Get the url query parameters.
   *
   * The location data is taken from url instead of elastic query for
   * a reason unknown.
   *
   * @return string
   *   The elasticsearch query.
   */
  public function getQueryParameters(): string {
    return $this->queryParameters;
  }

}
