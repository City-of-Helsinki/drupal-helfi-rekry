<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_rekry_content\Kernel;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\helfi_rekry_content\Entity\JobListing;
use Drupal\helfi_rekry_content\Service\JobListingCleaner;
use Drupal\node\NodeInterface;
use Drupal\Tests\helfi_rekry_content\Traits\HelbitTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the job listing cleaner service.
 */
#[RunTestsInSeparateProcesses]
#[Group('helfi_rekry_content')]
class JobListingCleanerTest extends RekryKernelTestBase {

  use HelbitTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
    'text',
    'filter',
    'datetime',
    'taxonomy',
    'language',
    'content_translation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['field', 'node', 'filter', 'language', 'content_translation']);

    $this->createJobListingContentType();
  }

  /**
   * Data provider for testDeleteExpired().
   *
   * @return array<string, array<string, mixed>>
   *   Test cases keyed by description.
   */
  public static function deleteExpiredDataProvider(): array {
    return [
      'expired listing gone from the API is deleted' => [
        'apiListingIds' => ['TESTI-9999-99-9999'],
        'recruitmentId' => 'TESTI-1234-56-7890',
        'publicationEnds' => '-1 year',
        'changed' => NULL,
        'status' => NodeInterface::NOT_PUBLISHED,
        'expectedCount' => 1,
        'expectedDeleted' => TRUE,
      ],
      'expired listing still in the API is kept' => [
        'apiListingIds' => ['TESTI-2345-67-8901'],
        'recruitmentId' => 'TESTI-2345-67-8901',
        'publicationEnds' => '-1 year',
        'changed' => NULL,
        'status' => NodeInterface::NOT_PUBLISHED,
        'expectedCount' => 0,
        'expectedDeleted' => FALSE,
      ],
      'expired listing is skipped when the API returns nothing' => [
        'apiListingIds' => [],
        'recruitmentId' => 'TESTI-3456-78-9012',
        'publicationEnds' => '-1 year',
        'changed' => NULL,
        'status' => NodeInterface::NOT_PUBLISHED,
        'expectedCount' => 0,
        'expectedDeleted' => FALSE,
      ],
      'listing within the threshold is ignored' => [
        'apiListingIds' => ['TESTI-9999-99-9999'],
        'recruitmentId' => 'TESTI-4567-89-0123',
        'publicationEnds' => '-1 week',
        'changed' => NULL,
        'status' => NodeInterface::NOT_PUBLISHED,
        'expectedCount' => 0,
        'expectedDeleted' => FALSE,
      ],
      'published listing is ignored' => [
        'apiListingIds' => ['TESTI-9999-99-9999'],
        'recruitmentId' => 'TESTI-5678-90-1234',
        'publicationEnds' => '-1 year',
        'changed' => NULL,
        'status' => NodeInterface::PUBLISHED,
        'expectedCount' => 0,
        'expectedDeleted' => FALSE,
      ],
      'legacy listing without end date is deleted' => [
        'apiListingIds' => ['TESTI-9999-99-9999'],
        'recruitmentId' => 'TESTI-6789-01-2345',
        'publicationEnds' => NULL,
        'changed' => '-2 years',
        'status' => NodeInterface::NOT_PUBLISHED,
        'expectedCount' => 1,
        'expectedDeleted' => TRUE,
      ],
    ];
  }

  /**
   * Tests that expired job listings are cleaned according to the API state.
   *
   * @phpstan-param string[] $apiListingIds
   */
  #[DataProvider('deleteExpiredDataProvider')]
  public function testDeleteExpired(
    array $apiListingIds,
    string $recruitmentId,
    ?string $publicationEnds,
    ?string $changed,
    int $status,
    int $expectedCount,
    bool $expectedDeleted,
  ): void {
    $this->mockHelbitClient([
      'en' => array_map(
        fn (string $id): array => $this->jobRow($id, 'Still open', 'Address'),
        $apiListingIds,
      ),
    ]);

    $node = $this->createJobListing(
      $recruitmentId,
      $publicationEnds !== NULL
        ? (new DrupalDateTime($publicationEnds, 'UTC'))->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT)
        : NULL,
      $status,
    );

    if ($changed !== NULL) {
      $node->setChangedTime((int) strtotime($changed));
      $node->save();
    }

    $sut = $this->container->get(JobListingCleaner::class);
    $this->assertSame($expectedCount, $sut->deleteExpired());

    $loaded = $this->container->get(EntityTypeManagerInterface::class)
      ->getStorage('node')
      ->loadUnchanged($node->id());

    if ($expectedDeleted) {
      $this->assertNull($loaded);
    }
    else {
      $this->assertInstanceOf(NodeInterface::class, $loaded);
    }
  }

  /**
   * Creates a job listing node.
   *
   * @param string $recruitmentId
   *   The recruitment id.
   * @param string|null $publicationEnds
   *   Storage formatted publication end date, or NULL for legacy listings.
   * @param int $status
   *   Publication status. Defaults to unpublished.
   *
   * @return \Drupal\helfi_rekry_content\Entity\JobListing
   *   The saved job listing.
   */
  private function createJobListing(string $recruitmentId, ?string $publicationEnds, int $status = NodeInterface::NOT_PUBLISHED): JobListing {
    $node = $this->container->get(EntityTypeManagerInterface::class)
      ->getStorage('node')
      ->create([
        'type' => 'job_listing',
        'langcode' => 'en',
        'title' => $this->randomMachineName(),
        'status' => $status,
        'field_recruitment_id' => $recruitmentId,
        'field_publication_ends' => $publicationEnds,
      ]);
    $node->save();
    $this->assertInstanceOf(JobListing::class, $node);

    return $node;
  }

}
