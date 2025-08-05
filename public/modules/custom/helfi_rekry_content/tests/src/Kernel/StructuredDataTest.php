<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_rekry_content\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\helfi_platform_config\EntityVersionMatcher;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Tests structured data functionality.
 *
 * Tests covered:
 * - Entity type detection for structured data injection
 * - Verification that non-JobListing entities receive no structured data.
 *
 * @group helfi_rekry_content
 */
class StructuredDataTest extends KernelTestBase {

  use ProphecyTrait;

  /**
   * Mocked entity version matcher.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $entityVersionMatcher;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
    'text',
    'datetime',
    'helfi_rekry_content',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installConfig(['node', 'field', 'text']);
    $this->installSchema('node', ['node_access']);

    NodeType::create([
      'type' => 'job_listing',
      'name' => 'Job Listing',
    ])->save();

    NodeType::create([
      'type' => 'page',
      'name' => 'Page',
    ])->save();

    $this->createRequiredFields();

    $this->entityVersionMatcher = $this->prophesize(EntityVersionMatcher::class);
    $this->entityVersionMatcher->getType()->willReturn(['entity' => NULL]);
    $this->container->set('helfi_platform_config.entity_version_matcher', $this->entityVersionMatcher->reveal());
  }

  /**
   * Tests that non-JobListing entities do not receive structured data.
   *
   * @covers ::helfi_rekry_content_page_attachments
   */
  public function testNoStructuredDataForNonJobListing(): void {
    $page = Node::create([
      'type' => 'page',
      'title' => 'Regular Page',
    ]);
    $page->save();

    $attachments = [];
    $this->mockEntityMatcher($page);
    helfi_rekry_content_page_attachments($attachments);

    $this->assertEmpty($attachments, 'No attachments should be added for non-JobListing entities');
  }

  /**
   * Creates minimal required fields for JobListing testing.
   */
  private function createRequiredFields(): void {
    FieldStorageConfig::create([
      'field_name' => 'field_job_description_override',
      'entity_type' => 'node',
      'type' => 'text_long',
    ])->save();

    FieldStorageConfig::create([
      'field_name' => 'field_organization_name',
      'entity_type' => 'node',
      'type' => 'string',
    ])->save();

    FieldStorageConfig::create([
      'field_name' => 'job_description',
      'entity_type' => 'node',
      'type' => 'text_long',
    ])->save();

    FieldStorageConfig::create([
      'field_name' => 'field_recruitment_id',
      'entity_type' => 'node',
      'type' => 'string',
    ])->save();

    FieldStorageConfig::create([
      'field_name' => 'field_publication_starts',
      'entity_type' => 'node',
      'type' => 'datetime',
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_job_description_override',
      'entity_type' => 'node',
      'bundle' => 'job_listing',
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_organization_name',
      'entity_type' => 'node',
      'bundle' => 'job_listing',
    ])->save();

    FieldConfig::create([
      'field_name' => 'job_description',
      'entity_type' => 'node',
      'bundle' => 'job_listing',
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_recruitment_id',
      'entity_type' => 'node',
      'bundle' => 'job_listing',
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_publication_starts',
      'entity_type' => 'node',
      'bundle' => 'job_listing',
    ])->save();
  }

  /**
   * Mocks the entity version matcher service.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to return from the matcher.
   */
  private function mockEntityMatcher($entity): void {
    $this->entityVersionMatcher->getType()->willReturn(['entity' => $entity]);
  }

  /**
   * Extracts JSON-LD structured data from page attachments.
   *
   * @param array $attachments
   *   Page attachments array.
   *
   * @return string
   *   The JSON-LD string or empty string if not found.
   */
  private function extractJsonLdFromAttachments(array $attachments): string {
    foreach ($attachments['#attached']['html_head'] as $head_element) {
      if (isset($head_element[1]) && $head_element[1] === 'structured_job_listing_data') {
        return $head_element[0]['#value'];
      }
    }
    return '';
  }

}
