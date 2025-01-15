<?php

declare(strict_types=1);

namespace Drupal\helfi_rekry_content\Entity;

use Drupal\Core\Language\LanguageInterface;
use Drupal\filter\Render\FilteredMarkup;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\TermInterface;

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
    return $this->get('field_job_description_override')->value
      ?: $this->get('job_description')->value
      ?? '';
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
   * Create formatted datetime string for job listing formatted data.
   *
   * @return string
   *   A formatted date string.
   */
  public function getFormattedStartTime(): string {
    /** @var \Drupal\Core\Datetime\DateFormatterInterface $date_formatter */
    $date_formatter = \Drupal::service('date.formatter');

    if ($this->get('field_publication_starts')->isEmpty()) {
      $publication_starts_datetime = $this->getCreatedTime();
    }
    else {
      // @phpstan-ignore-next-line
      $publication_starts_datetime = $this->get('field_publication_starts')->date->getTimestamp();
    }

    return $date_formatter->format($publication_starts_datetime, 'html_date');
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
   * @return \Drupal\taxonomy\TermInterface|false
   *   Returns the organization taxonomy term or false if not set.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function getOrganization() : TermInterface|FALSE {
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
      /** @var \Drupal\taxonomy\TermInterface $organization */
      $organization = $this->entityTypeManager()
        ->getStorage('taxonomy_term')
        ->load($organization_id);

      return $organization ?? FALSE;
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
    $image_style = [
      'type' => 'responsive_image',
      'label' => 'hidden',
      'settings' => [
        'responsive_image_style' => 'job_listing_org',
        'image_link' => '',
        'image_loading' => [
          'attribute' => 'eager',
        ],
      ],
    ];

    // Return the JobListing image field if it is set.
    if (!$this->get('field_image')->isEmpty()) {

      /** @var \Drupal\Core\Entity\Plugin\DataType\EntityReference $entity_reference */
      $entity_reference = $this->get('field_image')
        ?->first()
        ?->get('entity');

      /** @var \Drupal\Core\Entity\Plugin\DataType\EntityAdapter $entity_adapter */
      $entity_adapter = $entity_reference?->getTarget();

      /** @var \Drupal\media\Entity\Media $media */
      $media = $entity_adapter?->getEntity();

      // Render array of the image.
      return $media
        ?->get('field_media_image')
        ?->first()
        ?->view($image_style) ?? [];
    }

    $organization = $this->getOrganization();

    // Return the organization default image if it is set.
    if ($organization && !$organization->get('field_default_image')->isEmpty()) {
      return $organization->get('field_default_image')->first()->view($image_style);
    }

    // Return an empty array if no image is found.
    return [];
  }

  /**
   * Get the translated organization name.
   *
   * @return string
   *   Returns the translated organization name.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function getTranslatedOrganisationName() : string {
    $organization = $this->getOrganization();
    $organization_name = '';

    if ($organization) {
      $langcode = $this->languageManager()
        ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)
        ->getId();

      $organization_name = $organization->hasTranslation($langcode)
        ? $organization->getTranslation($langcode)->getName()
        : $organization->getName() ?? '';
    }
    return $organization_name;
  }

  /**
   * Get organization description.
   *
   * @return \Drupal\filter\Render\FilteredMarkup|string
   *   Organization description as a render array.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
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
    elseif (
      $organization_description->isEmpty() &&
      $organization && !$organization->get('description')->isEmpty()
    ) {
      $organization_description = $organization->get('description');
    }

    return $organization_description->processed ?? $organization_description->value;
  }

}
