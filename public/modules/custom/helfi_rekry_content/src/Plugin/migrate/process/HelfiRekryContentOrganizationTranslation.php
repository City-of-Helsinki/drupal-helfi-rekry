<?php

declare(strict_types=1);

namespace Drupal\helfi_rekry_content\Plugin\migrate\process;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Process plugin for Organization taxonomy term translations.
 *
 * @MigrateProcessPlugin(
 *   id = "helfi_rekry_content_organization_translation"
 * )
 *
 * @code
 * field_external_id:
 *   plugin: helfi_rekry_content_organization_translation
 *   source: id
 * @endcode
 */
class HelfiRekryContentOrganizationTranslation extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs a helfi_rekry_content_organization_translation process plugin.
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
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
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
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property): int {
    $external_id = $value;

    // Get the Finnish term with the external ID.
    $finnish_term_query = $this->entityTypeManager
      ->getStorage('taxonomy_term')
      ->loadByProperties([
        'field_external_id' => $external_id,
        'langcode' => 'fi',
      ]);

    $finnish_term = reset($finnish_term_query);

    // Get the migration langcode.
    $migration_langcode = $row->getRawDestination()['langcode'];

    // If a translation already exists, remove it.
    if ($finnish_term->hasTranslation($migration_langcode)) {
      $finnish_term->removeTranslation($migration_langcode);
    }

    // Add translation to the term & save.
    $finnish_term->addTranslation($migration_langcode, [
      'name' => $row->getSource()['name'],
    ])->save();

    // Don't add the term if it's not in Finnish.
    if ($migration_langcode != 'fi') {
      throw new MigrateSkipRowException();
    }

    return $external_id;
  }

}
