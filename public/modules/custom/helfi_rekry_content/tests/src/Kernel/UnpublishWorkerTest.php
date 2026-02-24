<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_rekry_content\Kernel;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\NodeInterface;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Unpublish worker test.
 */
#[RunTestsInSeparateProcesses]
#[Group('helfi_rekry_content')]
class UnpublishWorkerTest extends RekryKernelTestBase {

  use NodeCreationTrait;
  use ContentTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'node',
    'field',
    'user',
    'text',
    'filter',
    'scheduler',
    'language',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installSchema('node', 'node_access');
    $this->installConfig(['node', 'filter']);
    $this->createContentType(['type' => 'job_listing']);
  }

  /**
   * Tests unpublish worker.
   */
  public function testUnpublishWorker(): void {
    $manager = $this->container->get('plugin.manager.queue_worker');
    $sut = $manager->createInstance('job_listing_unpublish_worker');

    $published_node = $this->createNode([
      'type' => 'job_listing',
      'status' => NodeInterface::PUBLISHED,
    ]);

    $scheduled_node = $this->createNode([
      'type' => 'job_listing',
      'status' => NodeInterface::NOT_PUBLISHED,
    ]);

    ConfigurableLanguage::createFromLangcode('sv')->save();
    $translated_node = $this->createNode([
      'type' => 'job_listing',
      'langcode' => 'en',
      'status' => NodeInterface::NOT_PUBLISHED,
    ]);
    $translated_node->addTranslation('sv', [
      'title' => $this->randomMachineName(),
      'status' => NodeInterface::PUBLISHED,
    ])->save();

    $sut->processItem(['nid' => $published_node->id()]);
    $this->assertNodeIsUnpublished($published_node->id());

    $sut->processItem(['nid' => $scheduled_node->id()]);
    $this->assertNodeIsUnpublished($scheduled_node->id());

    $sut->processItem(['nid' => $translated_node->id()]);
    $this->assertNodeIsUnpublished($translated_node->id());
  }

  /**
   * Asserts that node is no longer published.
   */
  private function assertNodeIsUnpublished(string $nid): void {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->container->get(EntityTypeManagerInterface::class)
      ->getStorage('node')
      ->load($nid);

    foreach ($node->getTranslationLanguages() as $language) {
      $translation = $node->getTranslation($language->getId());
      $this->assertFalse($translation->isPublished());
      $this->assertEmpty($translation->get('publish_on')->getValue());
    }
  }

}
