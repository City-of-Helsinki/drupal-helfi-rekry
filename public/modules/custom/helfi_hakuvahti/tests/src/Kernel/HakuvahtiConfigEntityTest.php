<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_hakuvahti\Kernel;

use Drupal\helfi_hakuvahti\Entity\HakuvahtiConfig;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests for Hakuvahti configuration entity.
 *
 * @group helfi_hakuvahti
 */
class HakuvahtiConfigEntityTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['helfi_hakuvahti'];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['helfi_hakuvahti']);
    $this->entityTypeManager = $this->container->get('entity_type.manager');
  }

  /**
   * Tests creating a hakuvahti configuration.
   */
  public function testCreateConfig(): void {
    $storage = $this->entityTypeManager->getStorage('hakuvahti_config');

    $config = $storage->create([
      'id' => 'test',
      'label' => 'Test Configuration',
      'site_id' => 'test-site-id',
    ]);
    $config->save();

    // Verify it was saved.
    /** @var \Drupal\helfi_hakuvahti\Entity\HakuvahtiConfig|null $loaded */
    $loaded = $storage->load('test');
    $this->assertInstanceOf(HakuvahtiConfig::class, $loaded);
    $this->assertEquals('test', $loaded->id());
    $this->assertEquals('Test Configuration', $loaded->label());
    $this->assertEquals('test-site-id', $loaded->getSiteId());
  }

  /**
   * Tests updating a hakuvahti configuration.
   */
  public function testUpdateConfig(): void {
    $storage = $this->entityTypeManager->getStorage('hakuvahti_config');

    // Create initial config.
    /** @var \Drupal\helfi_hakuvahti\Entity\HakuvahtiConfig $config */
    $config = $storage->create([
      'id' => 'test',
      'label' => 'Test',
      'site_id' => 'original-site',
    ]);
    $config->save();

    // Update site_id.
    $config->setSiteId('updated-site');
    $config->save();

    // Verify update persisted.
    /** @var \Drupal\helfi_hakuvahti\Entity\HakuvahtiConfig|null $loaded */
    $loaded = $storage->load('test');
    $this->assertEquals('updated-site', $loaded->getSiteId());
  }

  /**
   * Tests deleting a hakuvahti configuration.
   */
  public function testDeleteConfig(): void {
    $storage = $this->entityTypeManager->getStorage('hakuvahti_config');

    /** @var \Drupal\helfi_hakuvahti\Entity\HakuvahtiConfig $config */
    $config = $storage->create([
      'id' => 'test',
      'label' => 'Test',
      'site_id' => 'test-site',
    ]);
    $config->save();

    // Delete it.
    $config->delete();

    // Verify it's gone.
    /** @var \Drupal\helfi_hakuvahti\Entity\HakuvahtiConfig|null $loaded */
    $loaded = $storage->load('test');
    $this->assertNull($loaded);
  }

  /**
   * Tests multiple configurations can exist.
   */
  public function testMultipleConfigs(): void {
    $storage = $this->entityTypeManager->getStorage('hakuvahti_config');

    // Create multiple configs.
    $storage->create([
      'id' => 'jobs',
      'label' => 'Job Searches',
      'site_id' => 'jobs-site',
    ])->save();

    $storage->create([
      'id' => 'news',
      'label' => 'News Articles',
      'site_id' => 'news-site',
    ])->save();

    $storage->create([
      'id' => 'events',
      'label' => 'Events',
      'site_id' => 'events-site',
    ])->save();

    // Load all configs.
    /** @var array<string, \Drupal\helfi_hakuvahti\Entity\HakuvahtiConfig> $configs */
    $configs = $storage->loadMultiple();
    $this->assertCount(4, $configs, 'Should have 3 created configs plus default from YAML');

    // Verify each has correct site_id.
    $this->assertEquals('jobs-site', $configs['jobs']->getSiteId());
    $this->assertEquals('news-site', $configs['news']->getSiteId());
    $this->assertEquals('events-site', $configs['events']->getSiteId());
    $this->assertArrayHasKey('default', $configs, 'Default config should exist from YAML');
  }

  /**
   * Tests getSiteId() method.
   */
  public function testGetSiteId(): void {
    $storage = $this->entityTypeManager->getStorage('hakuvahti_config');

    /** @var \Drupal\helfi_hakuvahti\Entity\HakuvahtiConfig $config */
    $config = $storage->create([
      'id' => 'test',
      'label' => 'Test',
      'site_id' => 'my-site-id',
    ]);

    $this->assertEquals('my-site-id', $config->getSiteId());
  }

  /**
   * Tests setSiteId() method returns fluent interface.
   */
  public function testSetSiteIdFluentInterface(): void {
    $storage = $this->entityTypeManager->getStorage('hakuvahti_config');

    /** @var \Drupal\helfi_hakuvahti\Entity\HakuvahtiConfig $config */
    $config = $storage->create([
      'id' => 'test',
      'label' => 'Test',
      'site_id' => 'original',
    ]);

    $result = $config->setSiteId('new-site');

    // Verify fluent interface.
    $this->assertSame($config, $result);
    $this->assertEquals('new-site', $config->getSiteId());
  }

  /**
   * Tests default site_id is empty string.
   */
  public function testDefaultSiteId(): void {
    $storage = $this->entityTypeManager->getStorage('hakuvahti_config');

    /** @var \Drupal\helfi_hakuvahti\Entity\HakuvahtiConfig $config */
    $config = $storage->create([
      'id' => 'test',
      'label' => 'Test',
    ]);

    $this->assertEquals('', $config->getSiteId());
  }

}
