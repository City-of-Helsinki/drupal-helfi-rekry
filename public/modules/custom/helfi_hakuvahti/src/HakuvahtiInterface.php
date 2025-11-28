<?php

declare(strict_types=1);

namespace Drupal\helfi_hakuvahti;

/**
 * Hakuvahti client.
 */
interface HakuvahtiInterface {

  /**
   * Create hakuvahti subscription.
   *
   * @throws \Drupal\helfi_hakuvahti\HakuvahtiException
   */
  public function subscribe(HakuvahtiRequest $request): void;

  /**
   * Confirm hakuvahti subscription.
   *
   * @throws \Drupal\helfi_hakuvahti\HakuvahtiException
   */
  public function confirm(string $subscriptionHash, string $subscriptionId): void;

  /**
   * Renew hakuvahti subscription.
   *
   * @throws \Drupal\helfi_hakuvahti\HakuvahtiException
   */
  public function renew(string $subscriptionHash, string $subscriptionId): void;

  /**
   * Unsubscribe hakuvahti subscription.
   *
   * @throws \Drupal\helfi_hakuvahti\HakuvahtiException
   */
  public function unsubscribe(string $hash, string $subscription): void;

}
