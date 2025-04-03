<?php

declare(strict_types=1);

namespace Drupal\helfi_rekry_content\Helbit;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Factory class for settings objects.
 */
final readonly class SettingsFactory {

  /**
   * Constructs a SettingsFactory object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(private ConfigFactoryInterface $configFactory) {
  }

  /**
   * Constructs a new Settings object.
   *
   * @return \Drupal\helfi_rekry_content\Helbit\Settings
   *   The Helbit settings object.
   */
  public function create(): Settings {
    $config = $this->configFactory->get('helfi_rekry_content.settings');
    $clients = array_map(
      static fn (array $client) => new HelbitEnvironment(
        $client['client_id'] ?: '',
        $client['base_url'] ?: ''
      ),
      $config->get('helbit_clients') ?: []
    );

    return new Settings($clients);
  }

}
