<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_rekry_content\Kernel\SchemaOrg;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\helfi_rekry_content\Entity\JobListing;
use Drupal\helfi_rekry_content\SchemaOrg\JobPostingBuilder;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\helfi_rekry_content\Kernel\RekryKernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the JobPosting schema.org builder against real job listing nodes.
 */
#[Group('helfi_rekry_content')]
#[RunTestsInSeparateProcesses]
final class JobPostingBuilderTest extends RekryKernelTestBase {

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
    'taxonomy',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('taxonomy_term');
    $this->installConfig(['system', 'node', 'field', 'text']);
    $this->installSchema('node', ['node_access']);

    NodeType::create(['type' => 'job_listing', 'name' => 'Job Listing'])->save();
    NodeType::create(['type' => 'page', 'name' => 'Page'])->save();

    $this->createJobListingFields();
  }

  /**
   * Tests that the builder only applies to job listing nodes.
   */
  public function testApplies(): void {
    $builder = $this->container->get(JobPostingBuilder::class);

    $page = Node::create(['type' => 'page', 'title' => 'Regular page']);
    $page->save();

    $this->assertTrue($builder->applies($this->createJobListing()));
    $this->assertFalse($builder->applies($page));
    $this->assertFalse($builder->applies(NULL));
  }

  /**
   * Tests that build() produces a JobPosting linked into the base graph.
   */
  public function testBuild(): void {
    $builder = $this->container->get(JobPostingBuilder::class);
    $job = $this->createJobListing();

    $cacheability = new CacheableMetadata();
    $result = $builder->build($job, $cacheability);

    $this->assertCount(1, $result);
    $posting = $result[0];

    $this->assertSame('JobPosting', $posting['@type']);
    $this->assertStringEndsWith('#jobposting', $posting['@id']);
    $this->assertSame('Test job', $posting['title']);
    $this->assertSame('Job description', $posting['description']);
    $this->assertSame('REKRY-123', $posting['identifier']['value']);
    $this->assertNotEmpty($posting['datePosted']);
    $this->assertSame('2026-07-01T13:00:00', $posting['validThrough']);
    $this->assertSame('Test Organization', $posting['hiringOrganization']['name']);
    $this->assertSame(
      'https://www.hel.fi/#organization',
      $posting['hiringOrganization']['parentOrganization']['@id']
    );
    $this->assertSame('Pursimiehenkatu 4', $posting['jobLocation']['address']['streetAddress']);
    $this->assertSame('00150', $posting['jobLocation']['address']['postalCode']);
    $this->assertStringEndsWith('#webpage', $posting['mainEntityOfPage']['@id']);

    // The builder registered the node and config as cache dependencies.
    $tags = $cacheability->getCacheTags();
    $this->assertNotEmpty(array_intersect($job->getCacheTags(), $tags));
  }

  /**
   * Creates a job listing node with the fields the builder reads.
   */
  private function createJobListing(): JobListing {
    $node = JobListing::create([
      'type' => 'job_listing',
      'title' => 'Test job',
      'job_description' => 'Job description',
      'field_recruitment_id' => 'REKRY-123',
      'field_publication_starts' => '2026-06-01T08:00:00',
      'field_publication_ends' => '2026-07-01T13:00:00',
      'field_organization_name' => 'Test Organization',
      'field_address' => 'Pursimiehenkatu 4',
      'field_postal_area' => 'Helsinki',
      'field_postal_code' => '00150',
    ]);
    $node->save();

    return $node;
  }

}
