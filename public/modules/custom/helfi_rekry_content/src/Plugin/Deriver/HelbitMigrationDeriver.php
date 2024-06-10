<?php

declare(strict_types=1);

namespace Drupal\helfi_rekry_content\Plugin\Deriver;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Url;
use Drupal\helfi_rekry_content\Helbit\Settings;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Deriver for Helbit migrations.
 */
final class HelbitMigrationDeriver extends DeriverBase implements ContainerDeriverInterface {

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\helfi_rekry_content\Helbit\Settings $config
   *   Helbit settings.
   */
  public function __construct(private readonly Settings $config) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) : self {
    return new self($container->get(Settings::class));
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition): array {
    $langcodes = ['fi', 'sv', 'en'];
    $derivatives = ['all', 'changed'];

    if ($base_plugin_definition['source']['plugin'] == 'helbit_open_jobs') {
      foreach ($derivatives as $key) {
        $this->derivatives[$key] = $this->getJobMigrationDerivativeValues($base_plugin_definition, $key);
      }
    }
    else {
      foreach ($langcodes as $langcode) {
        $this->derivatives[$langcode] = $this->getDerivativeValues($base_plugin_definition, $langcode);
      }
    }

    return $this->derivatives;
  }

  /**
   * Creates a derivative definition for job migration.
   *
   * @param array $base_plugin_definition
   *   Base migration definitions.
   * @param string $key
   *   Key for derivative.
   *
   * @return array
   *   Modified plugin definition for derivative.
   */
  private function getJobMigrationDerivativeValues(array $base_plugin_definition, string $key): array {
    if (str_starts_with($key, 'changed')) {
      $base_plugin_definition['source']['changed'] = TRUE;
    }

    return $base_plugin_definition;
  }

  /**
   * Creates a derivative definition for each available language.
   *
   * @param array $base_plugin_definition
   *   Base migration definitions.
   * @param string $langcode
   *   Langcode.
   *
   * @return array
   *   Modified plugin definition for derivative.
   */
  private function getDerivativeValues(array $base_plugin_definition, string $langcode): array {
    $base_plugin_definition['process']['langcode']['default_value'] = $langcode;

    // Adds api key to source URL.
    $url = Url::fromUri($base_plugin_definition['source']['url'], [
      'query' => [
        'client' => $this->config->clientId,
        'lang' => $langcode,
      ],
    ]);

    // toString method encodes colons, which the API does not support.
    $base_plugin_definition['source']['urls'] = [preg_replace('/%3A/', ':', $url->toString())];

    return $base_plugin_definition;
  }

}
