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
  public function unsubscribe(string $subscriptionHash, string $subscriptionId): void;

  /**
   * Get hakuvahti subscription status.
   *
   * @param string $subscriptionHash
   *   The subscription hash.
   * @param string $subscriptionId
   *   The subscription ID.
   *
   * @return string|null
   *   The subscription status or NULL if not found.
   *
   * @throws \Drupal\helfi_hakuvahti\HakuvahtiException
   */
  public function getStatus(string $subscriptionHash, string $subscriptionId): ?string;

}
