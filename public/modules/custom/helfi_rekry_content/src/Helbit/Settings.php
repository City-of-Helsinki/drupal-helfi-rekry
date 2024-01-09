<?php

namespace Drupal\helfi_rekry_content\Helbit;

/**
 * DTO for Helbit client settings.
 */
final readonly class Settings {

  /**
   * Constructs a settings object.
   *
   * @param string $clientId
   *   Helbit client id.
   */
  public function __construct(public string $clientId) {
  }

}
