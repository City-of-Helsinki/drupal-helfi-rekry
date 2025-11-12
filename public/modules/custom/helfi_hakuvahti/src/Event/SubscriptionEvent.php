<?php

declare(strict_types=1);

namespace Drupal\helfi_hakuvahti\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\helfi_hakuvahti\HakuvahtiRequest;

/**
 * Event which is dispatched when hakuvahti is created successfully.
 */
final class SubscriptionEvent extends Event {

  public function __construct(public readonly HakuvahtiRequest $request) {
  }

  /**
   * Get the elasticsearch query.
   *
   * @return string
   *   The elasticsearch query.
   */
  public function getQuery(): string {
    return $this->request->elasticQuery;
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
    return $this->request->query;
  }

}
