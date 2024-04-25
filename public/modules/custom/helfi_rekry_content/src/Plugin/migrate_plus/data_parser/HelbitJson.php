<?php

declare(strict_types=1);

namespace Drupal\helfi_rekry_content\Plugin\migrate_plus\data_parser;

use Drupal\migrate_plus\Plugin\migrate_plus\data_parser\Json;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Obtain JSON data for migration from Helbit.
 *
 * @DataParser(
 *   id = "helbit_json",
 *   title = @Translation("Helbit JSON")
 * )
 */
final class HelbitJson extends Json {

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ): self {
    /** @var self $instance */
    $instance = parent::create($container, $configuration, $plugin_id,
      $plugin_definition);
    $instance->logger = $container->get('logger.factory')->get('helfi_rekry_content');
    return $instance;
  }

  /**
   * Get source data from Api.
   *
   * If no jobs match the given parameters, the api doesn't return root object.
   * To avoid throwing unnecessary errors, first check if root object exists.
   * Check if response is valid by the returned status code.
   *
   * {@inheritdoc}
   */
  protected function getSourceData(string $url): array {
    $response = $this->getDataFetcherPlugin()->getResponseContent($url);

    $source_data = json_decode($response, TRUE);

    if ($source_data['status'] === 'OK') {
      if (isset($source_data[$this->itemSelector])) {
        $selectors = explode('/', trim((string) $this->itemSelector, '/'));
        foreach ($selectors as $selector) {
          if (!empty($selector) || $selector === '0') {
            $source_data = $source_data[$selector];
          }
        }
        return $source_data;
      }

      return [];
    }

    $this->logger->error('Failed retrieving data from Helbit. Request failed with code: @status_code', [
      '@status_code' => $source_data['status'],
    ]);

    return [];
  }

}
