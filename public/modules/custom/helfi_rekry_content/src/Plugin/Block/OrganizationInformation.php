<?php

declare(strict_types=1);

namespace Drupal\helfi_rekry_content\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\helfi_platform_config\EntityVersionMatcher;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'OrganizationInformation' block.
 *
 * @Block(
 *  id = "organization_information_block",
 *  admin_label = @Translation("Organization information block"),
 * )
 */
final class OrganizationInformation extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a new instance.
   *
   * @param array $configuration
   *   The configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\helfi_platform_config\EntityVersionMatcher $entityVersionMatcher
   *   The entity version matcher.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly EntityVersionMatcher $entityVersionMatcher,
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ) : self {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('helfi_platform_config.entity_version_matcher'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $matcher = $this->entityVersionMatcher->getType();

    if (
      !$matcher['entity'] ||
      $matcher['entity_version'] == EntityVersionMatcher::ENTITY_VERSION_REVISION
    ) {
      return parent::getCacheTags();
    }
    return Cache::mergeTags(parent::getCacheTags(), $matcher['entity']->getCacheTags());
  }

  /**
   * {@inheritDoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['route']);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    // Get current entity and entity version.
    $entity_matcher = $this->entityVersionMatcher->getType();

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $entity_matcher['entity'];

    // Add the organization information to render array.
    if (
      $entity instanceof Node &&
      $entity->hasField('field_organization') &&
      $entity->hasField('field_organization_description')
    ) {
      $view_builder = $this->entityTypeManager->getViewBuilder('node');
      $build['organization_information'] = $view_builder->view($entity);
      $build['organization_information']['#theme'] = 'organization_information_block';
    }

    return $build;
  }

}
