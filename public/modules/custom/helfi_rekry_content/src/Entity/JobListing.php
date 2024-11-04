<?php

declare(strict_types=1);

namespace Drupal\helfi_rekry_content\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\filter\Render\FilteredMarkup;
use Drupal\node\Entity\Node;

/**
 * Bundle class for JobListing paragraph.
 */
class JobListing extends Node {

  /**
   * Get job description or override value.
   *
   * @return string
   *   Job description.
   */
  public function getJobDescription() : string {
    return $this->get('field_job_description_override')->value ?: $this->get('job_description')->value;
  }

  /**
   * Get translated organization name if available or override value.
   *
   * @return string
   *   Organization name.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function getOrganizationName() : string {
    if (!$this->get('field_organization_override')->first()) {
      return $this->get('field_organization_name')->value ?? '';
    }

    $storage = $this->entityTypeManager()
      ->getStorage('taxonomy_term');

    // @phpstan-ignore-next-line
    $organization_entity = $storage->load($this->get('field_organization_override')->first()->target_id);

    if (!$organization_entity->hasTranslation($this->get('langcode')->value)) {
      return $organization_entity->getName() ?? '';
    }

    $translated_organization_entity = $organization_entity->getTranslation($this->get('langcode')->value);
    return $translated_organization_entity->getName() ?? '';
  }

  /**
   * Get translated employment type if available.
   *
   * @return string
   *   Employment type.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function getEmploymentType() : string {
    if (!$this->get('field_employment_type')->first()) {
      return '';
    }

    $storage = $this->entityTypeManager()
      ->getStorage('taxonomy_term');

    // @phpstan-ignore-next-line
    $employment_type_entity = $storage->load($this->get('field_employment_type')->first()->target_id);

    if (!$employment_type_entity->hasTranslation($this->get('langcode')->value)) {
      return $employment_type_entity->getName();
    }

    $translated_employment_type_entity = $employment_type_entity->getTranslation($this->get('langcode')->value);
    return $translated_employment_type_entity->getName();

  }

  /**
   * Get city description fields.
   *
   * @return array
   *   City descriptions as an array.
   */
  public function getCityDescriptions() : array {
    $job_listings_config = \Drupal::config('helfi_rekry_content.job_listings');

    return [
      '#city_description_title' => $job_listings_config->get('city_description_title'),
      '#city_description_text' => $job_listings_config->get('city_description_text'),
    ];
  }

  /**
   * Get organization taxonomy term.
   *
   * @return \Drupal\Core\Entity\EntityInterface|bool
   *   Returns the organization taxonomy term or false if not set.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function getOrganization() : EntityInterface|bool {
    $organization_id = '';

    // Get the organization id from the migrated field.
    if (!$this->get('field_organization')->isEmpty()) {
      $organization_id = $this->get('field_organization')
        ->first()
        ->get('target_id')
        ->getValue();
    }

    // Use the organization override value if it is set.
    if (!$this->get('field_organization_override')->isEmpty()) {
      $organization_id = $this->get('field_organization_override')
        ->first()
        ->get('target_id')
        ->getValue();
    }

    try {
      return \Drupal::entityTypeManager()
        ->getStorage('taxonomy_term')
        ->load($organization_id);
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Get organization default image.
   *
   * @return array
   *   Returns a render array of the image.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function getOrganizationDefaultImage() : array {
    $organization = $this->getOrganization();

    if ($organization && !$organization->get('field_default_image')->isEmpty()) {
      return $organization->get('field_default_image')->first()->view([
        'type' => 'responsive_image',
        'label' => 'hidden',
        'settings' => [
          'responsive_image_style' => 'job_listing_org',
          'image_link' => '',
          'image_loading' => [
            'attribute' => 'eager',
          ],
        ],
      ]);
    }

    // Return the JobListing image.
    return $this->get('field_image')->value ?? [];
  }

  /**
   * Get organization description.
   *
   * @return \Drupal\filter\Render\FilteredMarkup|string
   *   Organization description as a render array.
   */
  public function getOrganizationDescription() : FilteredMarkup|string {
    $organization = $this->getOrganization();

    // Set organization description from node.
    $organization_description = $this->get('field_organization_description');

    // Check if the organization description override is set and use it.
    if (!$this->get('field_organization_description_o')->isEmpty()) {
      $organization_description = $this->get('field_organization_description_o');
    }
    // If not and the organization description is empty,
    // check if the organization taxonomy description is set and use it.
    elseif ($organization_description->isEmpty() && !$organization->get('description')->isEmpty()) {
      $organization_description = $organization->get('description');
    }

    return $organization_description->processed ?? $organization_description->value;
  }

}
