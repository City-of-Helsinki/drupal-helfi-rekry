<?php

declare(strict_types=1);

namespace Drupal\helfi_rekry_content\Plugin\migrate\source;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\helfi_rekry_content\Helbit\HelbitClient;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Source plugin for helbit open jobs.
 *
 * @MigrateSource(
 *   id = "helbit_open_jobs"
 * )
 */
class HelbitOpenJobs extends SourcePluginBase implements ContainerFactoryPluginInterface {

  /**
   * Helbit client.
   *
   * @var \Drupal\helfi_rekry_content\Helbit\HelbitClient
   */
  private HelbitClient $helbit;

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
    MigrationInterface $migration = NULL,
  ): self {
    $instance = new self($configuration, $plugin_id, $plugin_definition, $migration);
    $instance->helbit = $container->get(HelbitClient::class);
    return $instance;
  }

  /**
   * {@inheritDoc}
   */
  protected function initializeIterator(): \Iterator {
    $ids = $this->getIds();
    $langcodes = $this->configuration['langcodes'] ?? ['fi', 'sv', 'en'];

    $query = [];

    if ($this->configuration['changed'] ?? FALSE) {
      $query['timestamp'] = date('Y-m-d\TH:m:i', strtotime('-1 day'));
    }

    foreach ($langcodes as $langcode) {
      foreach ($this->helbit->getJobListings($langcode, $query) as $row) {
        $fields = $this->getFieldsFromRow($row) + [
          'langcode' => $langcode
        ];

        // Check that all ids are present in this row. E.g. not all job
        // listings have `employmentId` or `employmentTypeId` fields.
        // Skip this row if any id field is missing.
        foreach ($ids as $id => $options) {
          if (!isset($fields[$id])) {
            continue 2;
          }
        }

        yield $fields;
      }
    }
  }

  /**
   * Get configured fields from Helbit response.
   *
   * @return array
   *   Configured fields.
   */
  private function getFieldsFromRow(array $row): array {
    $fields = $this->configuration['fields'];
    $result = [];

    foreach ($fields as $field) {
      ['name' => $name, 'selector' => $selector] = $field;

      // Start from top level.
      $nested = $row;

      // Read nested data from helbit response.
      foreach (explode('/', $selector) as $key) {
        if (!isset($nested[$key])) {
          // Skip this field.
          continue 2;
        }

        $nested = $nested[$key];
      }

      // Nested field found.
      $result[$name] = $nested;
    }

    return $result;
  }

  /**
   * {@inheritDoc}
   */
  public function __toString(): string {
    return 'helbit_open_jobs';
  }

  /**
   * {@inheritDoc}
   */
  public function fields(): array {
    $fields = [];

    foreach ($this->configuration['fields'] as $field) {
      if (isset($field['name'])) {
        $fields[$field['name']] = $field['label'] ?? '';
      }
    }

    return $fields;
  }

  /**
   * {@inheritDoc}
   */
  public function getIds(): array {
    if (empty($this->configuration['ids'])) {
      throw new \InvalidArgumentException("Missing ids configuration option");
    }

    return $this->configuration['ids'];
  }

}
