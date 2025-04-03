<?php

declare(strict_types=1);

namespace Drupal\helfi_rekry_content\Helbit;

/**
 * DTO for Helbit client settings.
 */
final readonly class Settings {

  /**
   * Constructs a settings object.
   *
   * @param \Drupal\helfi_rekry_content\Helbit\HelbitEnvironment[] $clients
   *   Helbit clients.
   */
  public function __construct(public array $clients) {
  }

}
