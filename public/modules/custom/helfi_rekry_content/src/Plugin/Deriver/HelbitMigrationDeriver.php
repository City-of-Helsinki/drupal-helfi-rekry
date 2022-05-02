<?php

namespace Drupal\helfi_rekry_content\Plugin\Deriver;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Deriver for Helbit migrations.
 */
class HelbitMigrationDeriver extends DeriverBase implements ContainerDeriverInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static();
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
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
        'client' => getenv('HELBIT_CLIENT_ID'),
      ],
    ];

    $simpleLangcodeMigrations = [
      'helfi_rekry_task_areas',
      'helfi_rekry_organizations',
    ];
    $simpleLangcode = in_array($base_plugin_definition['id'], $simpleLangcodeMigrations);

    // Set values for translation migrations.
    if (strpos($key, '_sv') !== FALSE) {
      $urlOptions['query']['lang'] = $simpleLangcode ? 'sv' : 'sv_SE';
      $base_plugin_definition['destination']['translations'] = TRUE;
      $base_plugin_definition['process']['langcode']['default_value'] = 'sv';
    }
    elseif (strpos($key, '_en') !== FALSE) {
      $urlOptions['query']['lang'] = $simpleLangcode ? 'en' : 'en_US';
      $base_plugin_definition['destination']['translations'] = TRUE;
      $base_plugin_definition['process']['langcode']['default_value'] = 'en';
    }
    else {
      $urlOptions['query']['lang'] = $simpleLangcode ? 'fi' : 'fi_FI';
    }

    if (strpos($key, 'changed') !== FALSE) {
      $urlOptions['query']['timestamp'] = date('Y-m-d\TH:m:i', strtotime('-1 day'));
    }

    $url = Url::fromUri($base_plugin_definition['source']['url'], $urlOptions)->toString();
    // toString method encodes colons, which the API does not support.
    $base_plugin_definition['source']['urls'] = [preg_replace('/%3A/', ':', $url)];

    return $base_plugin_definition;
  }

}
