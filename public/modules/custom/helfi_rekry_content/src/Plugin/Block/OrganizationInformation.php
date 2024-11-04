<?php

declare(strict_types=1);

namespace Drupal\helfi_rekry_content\Plugin\Block;

use Drupal\helfi_platform_config\Plugin\Block\ContentBlockBase;
use Drupal\helfi_rekry_content\Entity\JobListing;

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
      '#theme' => 'organization_information_block',
      '#cache' => ['tags' => $this->getCacheTags()],
    ];

    // Get current entity and entity version.
    $entity_matcher = $this->entityVersionMatcher->getType();

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $entity_matcher['entity'];

    // Add the organization information to render array.
    if ($entity instanceof JobListing) {
      // Get the City's title and description from the configuration.
      $build = $build + $entity->getCityDescriptions();

      // Get the organization entity and set the necessary variables.
      try {
        $build['#organization_image'] = $entity->getOrganizationDefaultImage();
        $build['#organization_title'] = $entity->getOrganizationName();
        $build['#organization_description'] = $entity->getOrganizationDescription();
      }
      catch (\Exception $e) {
      }
    }

    return $build;
  }

}
