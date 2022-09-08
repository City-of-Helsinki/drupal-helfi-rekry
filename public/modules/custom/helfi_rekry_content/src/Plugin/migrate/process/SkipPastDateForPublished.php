<?php

declare(strict_types = 1);

namespace Drupal\helfi_rekry_content\Plugin\migrate\process;

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
class SkipPastDateForPublished extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs a skip_past_date_for_published process plugin.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) : static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\migrate\MigrateSkipProcessException
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) : int {
    $valueTimestamp = strtotime($value);
    if ($valueTimestamp <= \Drupal::time()->getCurrentTime() && !empty($nid = $row->getDestinationProperty('nid'))) {
      $node = $this->entityTypeManager->getStorage('node')->load($nid);
      if (!empty($node) && $node->isPublished()) {
        // Value is in the past, node exists, and the node is already published.
        throw new MigrateSkipProcessException("The date is in the past and destination node is already published.");
      }
    }
    return $valueTimestamp;
  }

}
