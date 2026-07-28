<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_rekry_content\Kernel;

use Drupal\content_translation\ContentTranslationManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessageInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\Tests\helfi_api_base\Traits\MigrationTestTrait;
use Drupal\Tests\helfi_rekry_content\Traits\HelbitTestTrait;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests that the job listings migration.
 */
#[Group('helfi_rekry_content')]
class JobListingMigrationTest extends RekryKernelTestBase implements MigrateMessageInterface {

  use MigrationTestTrait;
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
    'migrate_plus',
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

    foreach (['fi', 'sv'] as $langcode) {
      ConfigurableLanguage::createFromLangcode($langcode)->save();
    }

    $this->createJobListingContentType();

    $this->container->get(ContentTranslationManagerInterface::class)
      ->setEnabled('node', 'job_listing', TRUE);
  }

  /**
   * Tests that same-recruitment-id rows migrate into one translated node.
   */
  public function testTranslationsMergeIntoSingleNode(): void {
    $this->mockHelbitClient([
      'fi' => [
        $this->jobRow('id1', 'Kehittäjä (fi)', 'Töölönlahdenkatu 2'),
        $this->jobRow('id2', 'Suunnittelija (fi)', 'Kansakoulukatu 3'),
      ],
      'sv' => [
        $this->jobRow('id1', 'Utvecklare (sv)', 'Tölöviksgatan 2'),
        $this->jobRow('id2', 'Planerare (sv)', 'Folkskolegatan 3'),
      ],
      'en' => [
        $this->jobRow('id1', 'Developer (en)', 'Töölönlahdenkatu 2'),
        $this->jobRow('id2', 'Designer (en)', 'Kansakoulukatu 3'),
      ],
    ]);

    $this->runJobsMigration();

    // Exactly two job listing nodes should exist.
    $nodeStorage = $this->container->get(EntityTypeManagerInterface::class)
      ->getStorage('node');
    $ids = $nodeStorage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'job_listing')
      ->execute();
    $this->assertCount(2, $ids);

    $expected = [
      'id1' => [
        'fi' => ['Kehittäjä (fi)', 'Töölönlahdenkatu 2'],
        'sv' => ['Utvecklare (sv)', 'Tölöviksgatan 2'],
        'en' => ['Developer (en)', 'Töölönlahdenkatu 2'],
      ],
      'id2' => [
        'fi' => ['Suunnittelija (fi)', 'Kansakoulukatu 3'],
        'sv' => ['Planerare (sv)', 'Folkskolegatan 3'],
        'en' => ['Designer (en)', 'Kansakoulukatu 3'],
      ],
    ];

    foreach ($expected as $recruitmentId => $translations) {
      $recruitmentId = (string) $recruitmentId;
      $node = $this->loadByRecruitmentId($recruitmentId);

      // Finnish is imported first, so it is the default translation.
      $this->assertSame('fi', $node->getUntranslated()->language()->getId());
      $this->assertTrue($node->getTranslation('fi')->isDefaultTranslation());

      foreach ($translations as $langcode => [$title, $address]) {
        $this->assertTrue($node->hasTranslation($langcode));
        $translation = $node->getTranslation($langcode);
        $this->assertSame($title, $translation->label());
        $this->assertSame($address, $translation->get('field_address')->value);
        $this->assertSame($recruitmentId, $translation->get('field_recruitment_id')->value);
      }
    }

    // Re-running the migration must not create duplicate nodes.
    $this->runJobsMigration();
    $ids = $nodeStorage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'job_listing')
      ->execute();
    $this->assertCount(2, $ids);
  }

  /**
   * Runs the real job listings migration with a simplified process pipeline.
   */
  private function runJobsMigration(): void {
    $manager = $this->container->get('plugin.manager.migration');
    $definition = $manager->getDefinition('helfi_rekry_jobs');

    $supported = [
      'nid',
      'title',
      'langcode',
      'field_address',
      'field_recruitment_id',
      'type',
    ];
    $definition['process'] = array_intersect_key(
      $definition['process'],
      array_flip($supported),
    );

    $migration = $manager->createStubMigration($definition);
    (new MigrateExecutable($migration, $this))->import();
  }

  /**
   * Loads a job listing node with the given recruitment id.
   */
  private function loadByRecruitmentId(string $recruitmentId): NodeInterface {
    $nodeStorage = $this->container->get(EntityTypeManagerInterface::class)->getStorage('node');
    $ids = $nodeStorage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'job_listing')
      ->condition('field_recruitment_id', $recruitmentId)
      ->execute();
    $this->assertCount(1, $ids);

    return Node::load(reset($ids));
  }

}
