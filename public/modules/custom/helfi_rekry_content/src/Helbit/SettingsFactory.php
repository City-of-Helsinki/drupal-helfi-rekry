<?php

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
   *   The helbit settings object.
   */
  public function create(): Settings {
    $config = $this->configFactory->get('helfi_rekry_content.settings');

    return new Settings($config->get('helbit_client_id') ?: '');
  }

}
