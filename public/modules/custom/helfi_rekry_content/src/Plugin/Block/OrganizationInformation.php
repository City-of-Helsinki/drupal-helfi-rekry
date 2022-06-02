<?php

namespace Drupal\helfi_rekry_content\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\hdbt_content\EntityVersionMatcher;
use Drupal\node\Entity\Node;

/**
 * Provides a 'OrganizationInformation' block.
 *
 * @Block(
 *  id = "organization_information_block",
 *  admin_label = @Translation("Organization information block"),
 * )
 */
class OrganizationInformation extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $matcher = \Drupal::service('hdbt_content.entity_version_matcher')->getType();

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
    $entity_matcher = \Drupal::service('hdbt_content.entity_version_matcher')->getType();

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $entity_matcher['entity'];

    // Add the organization information to render array.
    if (
      $entity instanceof Node &&
      $entity->hasField('field_organization_name') &&
      $entity->hasField('field_organization_description')
    ) {
      $view_builder = \Drupal::entityTypeManager()->getViewBuilder('node');
      $build['organization_information'] = $view_builder->view($entity);
      $build['organization_information']['#theme'] = 'organization_information_block';
    }

    return $build;
  }

}
