<?php

namespace Drupal\helfi_rekry_content\Entity;

use Drupal\node\Entity\Node;

/**
 * Bundle class for hel_map paragraph.
 */
class HelJobListing extends Node {
  /**
   * Constructor.
   *
   * @param array $values
   *   Values that the entity contains.
   * @param $entity_type
   *   Entity type.
   * @param $bundle
   *   Entity bundle.
   * @param $translations
   *   Entity translations.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(array $values, $entity_type, $bundle = FALSE, $translations = []) {
    parent::__construct($values, $entity_type, $bundle, $translations);

    $this->storage = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term');
  }

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
      return $this->get('field_organization_name')->value;
    }

    $organization_entity = $this->storage->load($this->get('field_organization_override')->first()->target_id);

    if (!$organization_entity->hasTranslation($this->get('langcode')->value)) {
      return $organization_entity->getName();
    }

    $translated_organization_entity = $organization_entity->getTranslation($this->get('langcode')->value);
    return $translated_organization_entity->getName();
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

    $employment_type_entity = $this->storage->load($this->get('field_employment_type')->first()->target_id);

    if (!$employment_type_entity->hasTranslation($this->get('langcode')->value)) {
      return $employment_type_entity->getName();
    }

    $translated_employment_type_entity = $employment_type_entity->getTranslation($this->get('langcode')->value);
    return $translated_employment_type_entity->getName();

  }

}
