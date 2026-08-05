<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_rekry_content\Kernel\Plugin\SearchApi;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\helfi_rekry_content\Entity\JobListing;
use Drupal\node\NodeInterface;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Processor\ProcessorInterface;
use Drupal\search_api\Utility\FieldsHelperInterface;
use Drupal\search_api\Utility\PluginHelperInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\helfi_rekry_content\Kernel\RekryKernelTestBase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the job listing entity status processor.
 */
#[RunTestsInSeparateProcesses]
#[Group('helfi_rekry_content')]
class JobListingEntityStatusTest extends RekryKernelTestBase {

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
    'views',
    'scheduler',
    'search_api',
  ];

  /**
   * The search index the processor is tested against.
   */
  private IndexInterface $index;

  /**
   * The processor under test.
   */
  private ProcessorInterface $processor;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('taxonomy_term');
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['field', 'node', 'filter']);

    $this->createJobListingContentType();

    Vocabulary::create([
      'vid' => 'task_area',
      'name' => 'Task area',
    ])->save();

    $this->index = Index::create([
      'id' => 'job_listings',
      'name' => 'Job listings',
      'datasource_settings' => [
        'entity:node' => [],
        'entity:taxonomy_term' => [],
      ],
      'tracker_settings' => [
        'default' => [],
      ],
    ]);

    $this->processor = $this->container->get(PluginHelperInterface::class)
      ->createProcessorPlugin($this->index, 'job_listing_entity_status');
  }

  /**
   * Data provider for testJobListingIndexing().
   *
   * @return array<string, array<string, mixed>>
   *   Test cases keyed by description.
   */
  public static function jobListingDataProvider(): array {
    return [
      'published listing is indexed' => [
        'status' => NodeInterface::PUBLISHED,
        'publishOn' => NULL,
        'publicationStarts' => '-1 month',
        'expectedIndexed' => TRUE,
      ],
      'scheduled listing is not indexed' => [
        'status' => NodeInterface::NOT_PUBLISHED,
        'publishOn' => '+1 week',
        'publicationStarts' => '+1 week',
        'expectedIndexed' => FALSE,
      ],
      'expired listing is indexed' => [
        'status' => NodeInterface::NOT_PUBLISHED,
        'publishOn' => NULL,
        'publicationStarts' => '-1 month',
        'expectedIndexed' => TRUE,
      ],
      'never published listing removed from source is not indexed' => [
        'status' => NodeInterface::NOT_PUBLISHED,
        'publishOn' => NULL,
        'publicationStarts' => '+1 week',
        'expectedIndexed' => FALSE,
      ],
    ];
  }

  /**
   * Tests which job listings the processor keeps in the index.
   *
   * @param int $status
   *   Publication status of the listing.
   * @param string|null $publishOn
   *   Relative scheduled publish date, or NULL when not scheduled.
   * @param string|null $publicationStarts
   *   Relative publication start date, or NULL when not set.
   * @param bool $expectedIndexed
   *   Whether the listing is expected to stay in the index.
   */
  #[DataProvider('jobListingDataProvider')]
  public function testJobListingIndexing(
    int $status,
    ?string $publishOn,
    ?string $publicationStarts,
    bool $expectedIndexed,
  ): void {
    $node = $this->container->get(EntityTypeManagerInterface::class)
      ->getStorage('node')
      ->create([
        'type' => 'job_listing',
        'langcode' => 'en',
        'title' => $this->randomMachineName(),
        'status' => $status,
        'publish_on' => $publishOn !== NULL ? strtotime($publishOn) : NULL,
        'field_publication_starts' => $publicationStarts !== NULL
          ? (new DrupalDateTime($publicationStarts, 'UTC'))->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT)
          : NULL,
      ]);
    $node->save();
    $this->assertInstanceOf(JobListing::class, $node);

    $this->assertSame($expectedIndexed, $this->isKeptInIndex($node));
  }

  /**
   * Tests that unpublished taxonomy terms are still excluded.
   */
  public function testTaxonomyTermIndexing(): void {
    $published = Term::create([
      'vid' => 'task_area',
      'name' => 'Published term',
      'status' => 1,
    ]);
    $published->save();

    $unpublished = Term::create([
      'vid' => 'task_area',
      'name' => 'Unpublished term',
      'status' => 0,
    ]);
    $unpublished->save();

    $this->assertTrue($this->isKeptInIndex($published));
    $this->assertFalse($this->isKeptInIndex($unpublished));
  }

  /**
   * Runs the processor for the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to build a search item from.
   *
   * @return bool
   *   TRUE if the item survived alterIndexedItems().
   */
  private function isKeptInIndex(EntityInterface $entity): bool {
    $datasource = $this->index->getDatasource('entity:' . $entity->getEntityTypeId());
    $item = $this->container->get(FieldsHelperInterface::class)
      ->createItemFromObject($this->index, $entity->getTypedData(), NULL, $datasource);

    $items = [$item->getId() => $item];
    $this->processor->alterIndexedItems($items);

    return isset($items[$item->getId()]);
  }

}
