<?php

declare(strict_types = 1);

namespace Drupal\helfi_rekry_content\Plugin\Deriver;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Deriver for Helbit migrations.
 */
class HelbitMigrationDeriver extends DeriverBase implements ContainerDeriverInterface {

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   The settings service.
   */
  public function __construct(private ConfigFactory $config) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) : self {
    return new self($container->get('config.factory'));
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition): array {
    $derivatives = [
      'all',
      'changed',
      'all_sv',
      'changed_sv',
      'all_en',
      'changed_en',
    ];

    foreach ($derivatives as $key) {
      $derivative = $this->getDerivativeValues($base_plugin_definition, $key);
      $this->derivatives[$key] = $derivative;
    }

    return $this->derivatives;
  }

  /**
   * Creates a derivative definition for each available language.
   *
   * @param array $base_plugin_definition
   *   Base migration definitions.
   * @param string $key
   *   Key for derivative.
   *
   * @return array
   *   Modified plugin definition for derivative.
   */
  protected function getDerivativeValues(array $base_plugin_definition, string $key): array {
    $urlOptions = [
      'query' => [
        'client' => $this->config->get('helfi_rekry_content.settings')->get('helbit_client_id'),
      ],
    ];

    $simpleLangcodeMigrations = [
      'helfi_rekry_task_areas',
      'helfi_rekry_organizations',
    ];
    $simpleLangcode = in_array($base_plugin_definition['id'], $simpleLangcodeMigrations);

    // Set values for translation migrations.
    if (str_contains($key, '_sv')) {
      $urlOptions['query']['lang'] = $simpleLangcode ? 'sv' : 'sv_SE';
      $base_plugin_definition['destination']['translations'] = TRUE;
      $base_plugin_definition['process']['langcode']['default_value'] = 'sv';
    }
    elseif (str_contains($key, '_en')) {
      $urlOptions['query']['lang'] = $simpleLangcode ? 'en' : 'en_US';
      $base_plugin_definition['destination']['translations'] = TRUE;
      $base_plugin_definition['process']['langcode']['default_value'] = 'en';
    }
    else {
      $urlOptions['query']['lang'] = $simpleLangcode ? 'fi' : 'fi_FI';
    }

    if (str_contains($key, 'changed')) {
      $urlOptions['query']['timestamp'] = date('Y-m-d\TH:m:i', strtotime('-1 day'));
    }

    $url = Url::fromUri($base_plugin_definition['source']['url'], $urlOptions)->toString();
    // toString method encodes colons, which the API does not support.
    $base_plugin_definition['source']['urls'] = [preg_replace('/%3A/', ':', $url)];

    return $base_plugin_definition;
  }

}
