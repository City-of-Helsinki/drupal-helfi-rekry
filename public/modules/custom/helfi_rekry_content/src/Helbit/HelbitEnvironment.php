<?php

declare(strict_types=1);

namespace Drupal\helfi_rekry_content\Helbit;

/**
 * DTO for Helbit settings.
 */
final readonly class HelbitEnvironment {

  /**
   * Constructs a settings object.
   *
   * @param string $clientId
   *   Helbit client id.
   * @param string $baseUrl
   *   Helbit base URL.
   */
  public function __construct(
    public string $clientId,
    public string $baseUrl,
  ) {
  }

}
