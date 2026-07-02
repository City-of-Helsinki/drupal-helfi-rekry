<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_rekry_content\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;

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
   * Creates the job_listing content type and its fields.
   */
  protected function createJobListingContentType(): void {
    NodeType::create([
      'type' => 'job_listing',
      'name' => 'Job listing',
    ])->save();

    // Field name => [type, translatable].
    /** @var array<string, array{0: string, 1: bool}> $fields */
    $fields = [
      'field_job_description_override' => ['text_long', TRUE],
      'job_description' => ['text_long', TRUE],
      'field_recruitment_id' => ['string', FALSE],
      'field_publication_starts' => ['datetime', FALSE],
      'field_publication_ends' => ['datetime', FALSE],
      'field_organization_name' => ['string', TRUE],
      'field_address' => ['string', TRUE],
      'field_postal_area' => ['string', TRUE],
      'field_postal_code' => ['string', FALSE],
      'field_employment_type' => ['entity_reference', FALSE],
      'field_organization_override' => ['entity_reference', FALSE],
    ];

    foreach ($fields as $name => [$type, $translatable]) {
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
        'translatable' => $translatable,
      ])->save();
    }
  }

}
