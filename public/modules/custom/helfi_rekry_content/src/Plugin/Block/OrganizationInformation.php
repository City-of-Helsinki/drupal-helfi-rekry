<?php

declare(strict_types=1);

namespace Drupal\helfi_rekry_content\Plugin\Block;

use Drupal\helfi_platform_config\Plugin\Block\ContentBlockBase;
use Drupal\node\Entity\Node;

/**
 * Provides a 'OrganizationInformation' block.
 *
 * @Block(
 *  id = "organization_information_block",
 *  admin_label = @Translation("Organization information block"),
 * )
 */
final class OrganizationInformation extends ContentBlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() : array {
    $build = [
      'sidebar_content' => [
        '#theme' => 'sidebar_content_block',
      ],
      '#cache' => ['tags' => $this->getCacheTags()],
    ];

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
      $build['sidebar_content']['#computed'] = $view_builder->view($entity);
      $build['sidebar_content']['#computed']['#theme'] = 'organization_information_block';
    }

    return $build;
  }

}
