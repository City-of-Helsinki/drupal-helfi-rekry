<?php

declare(strict_types=1);

namespace Drupal\helfi_rekry_content\Plugin\migrate\process;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Process plugin to skip past dates with published destination.
 *
 * Skips processing if the date string represents a past date and destination
 * is already published. This prevents the situation when a past value is
 * imported and the scheduler temporarily updates the node as unpublished.
 *
 * @MigrateProcessPlugin(
 *   id = "skip_past_date_for_published"
 * )
 *
 * @code
 * publish_on:
 *   plugin: skip_past_date_for_published
 *   source: publication_starts
 * @endcode
 */
final class SkipPastDateForPublished extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a skip_past_date_for_published process plugin.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time interface.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    array $plugin_definition,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected TimeInterface $time,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) : self {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('datetime.time'),
    );
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\migrate\MigrateSkipProcessException
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) : string {
    if ((int) $value && (int) $value <= $this->time->getCurrentTime() && !empty($nid = $row->getDestinationProperty('nid'))) {
      $node = $this->entityTypeManager->getStorage('node')->load($nid);
      if (!empty($node) && $node->isPublished()) {
        // Value is in the past, node exists, and the node is already published.
        throw new MigrateSkipProcessException("The date is in the past and destination node is already published.");
      }
    }
    return $value;
  }

}
