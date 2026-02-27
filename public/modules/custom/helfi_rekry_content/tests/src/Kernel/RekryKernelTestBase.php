<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_rekry_content\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;

/**
 * Base class for helfi_rekry_content kernel tests.
 */
abstract class RekryKernelTestBase extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'helfi_rekry_content',
    'helfi_hakuvahti',
    'migrate',
  ];

  /**
   * Creates the job_listing fields used by the helfi_rekry_content code.
   */
  protected function createJobListingFields(): void {
    $fields = [
      'field_job_description_override' => 'text_long',
      'job_description' => 'text_long',
      'field_recruitment_id' => 'string',
      'field_publication_starts' => 'datetime',
      'field_publication_ends' => 'datetime',
      'field_organization_name' => 'string',
      'field_address' => 'string',
      'field_postal_area' => 'string',
      'field_postal_code' => 'string',
      'field_employment_type' => 'entity_reference',
      'field_organization_override' => 'entity_reference',
    ];

    foreach ($fields as $name => $type) {
      $settings = $type === 'entity_reference' ? ['target_type' => 'taxonomy_term'] : [];
      FieldStorageConfig::create([
        'field_name' => $name,
        'entity_type' => 'node',
        'type' => $type,
        'settings' => $settings,
      ])->save();
      FieldConfig::create([
        'field_name' => $name,
        'entity_type' => 'node',
        'bundle' => 'job_listing',
      ])->save();
    }
  }

}
