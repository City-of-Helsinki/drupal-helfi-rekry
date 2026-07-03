<?php

declare(strict_types=1);

namespace Drupal\helfi_rekry_content\SchemaOrg;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\helfi_platform_config\SchemaOrg\EntityIdTrait;
use Drupal\helfi_platform_config\SchemaOrg\SchemaBuilderInterface;
use Drupal\helfi_rekry_content\Entity\JobListing;

/**
 * Adds JobPosting structured data for job listing pages.
 */
final class JobPostingBuilder implements SchemaBuilderInterface {

  use EntityIdTrait;

  public function __construct(
    private readonly ConfigFactoryInterface $configFactory,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function applies(?EntityInterface $entity): bool {
    return $entity instanceof JobListing;
  }

  /**
   * {@inheritdoc}
   */
  public function build(?EntityInterface $entity, RefinableCacheableDependencyInterface $cacheability): array {
    assert($entity instanceof JobListing);

    $config = $this->configFactory->get('helfi_platform_config.schema_settings');
    $organizationId = $config->get('organization_id') ?: self::DEFAULT_ORGANIZATION_ID;

    // The posting depends on the job listing node and the identity config.
    $cacheability
      ->addCacheableDependency($entity)
      ->addCacheableDependency($config);

    return [
      [
        '@type' => 'JobPosting',
        '@id' => $this->buildId($entity, 'jobposting'),
        'title' => $entity->getTitle(),
        'description' => $entity->getJobDescription(),
        'identifier' => [
          '@type' => 'PropertyValue',
          'name' => 'City of Helsinki',
          'value' => $entity->getRecruitmentId(),
        ],
        'datePosted' => $entity->getFormattedStartTime(),
        'validThrough' => $entity->get('field_publication_ends')->value,
        'employmentType' => $entity->getEmploymentType(),
        'hiringOrganization' => [
          '@type' => 'Organization',
          'name' => $entity->getOrganizationName(),
          'sameAs' => 'https://hel.fi/',
          // Link to the sitewide City of Helsinki identity in the base graph.
          'parentOrganization' => ['@id' => $organizationId],
        ],
        'jobLocation' => [
          '@type' => 'Place',
          'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => $entity->get('field_address')->value,
            'addressRegion' => $entity->get('field_postal_area')->value,
            'postalCode' => $entity->get('field_postal_code')->value,
            'addressCountry' => 'Finland',
          ],
        ],
        // Tie the posting to the page-level WebPage entity from the base graph.
        'mainEntityOfPage' => ['@id' => $this->buildId($entity, 'webpage')],
      ],
    ];
  }

}
