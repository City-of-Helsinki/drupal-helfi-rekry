<?php

declare(strict_types=1);

namespace Drupal\helfi_hakuvahti\Event;

use Drupal\helfi_hakuvahti\HakuvahtiRequest;

/**
 * Alter subscription event.
 */
final class SubscriptionAlterEvent {

  public function __construct(
    private HakuvahtiRequest $request,
  ) {
  }

  /**
   * Gets hakuvahti request.
   */
  public function getHakuvahtiRequest(): HakuvahtiRequest {
    return $this->request;
  }

  /**
   * Sets hakuvahti request.
   */
  public function setHakuvahtiRequest(HakuvahtiRequest $request): void {
    $this->request = $request;
  }

}
