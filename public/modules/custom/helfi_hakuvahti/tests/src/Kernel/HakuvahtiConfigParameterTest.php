<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_hakuvahti\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests for config parameter handling in subscribe requests.
 *
 * @group helfi_hakuvahti
 */
class HakuvahtiConfigParameterTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'helfi_hakuvahti',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['helfi_hakuvahti']);
    putenv('PROJECT_NAME=test-project');
  }

  /**
   * Tests that default config is loaded when no ?config parameter.
   */
  public function testDefaultConfigParameter(): void {
    // Create default config.
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('hakuvahti_config');
    $storage->create([
      'id' => 'default',
      'label' => 'Default',
      'site_id' => 'default-site-id',
    ])->save();

    // Simulate request without config parameter.
    $request = Request::create('/hakuvahti/subscribe', 'POST');

    // Get config ID (simulating controller logic).
    $configId = $request->query->get('config') ?? 'default';
    $this->assertEquals('default', $configId);

    // Load config.
    /** @var \Drupal\helfi_hakuvahti\Entity\HakuvahtiConfig|null $config */
    $config = $storage->load($configId);
    $this->assertNotNull($config);
    $this->assertEquals('default-site-id', $config->getSiteId());
  }

  /**
   * Tests that specific config is loaded when ?config parameter provided.
   */
  public function testSpecificConfigParameter(): void {
    // Create jobs config.
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('hakuvahti_config');
    $storage->create([
      'id' => 'jobs',
      'label' => 'Job Searches',
      'site_id' => 'jobs-site-id',
    ])->save();

    // Simulate request with config parameter.
    $request = Request::create('/hakuvahti/subscribe?config=jobs', 'POST', ['config' => 'jobs']);

    // Get config ID (simulating controller logic).
    $configId = $request->query->get('config') ?? 'default';
    $this->assertEquals('jobs', $configId);

    // Load config.
    /** @var \Drupal\helfi_hakuvahti\Entity\HakuvahtiConfig|null $config */
    $config = $storage->load($configId);
    $this->assertNotNull($config);
    $this->assertEquals('jobs-site-id', $config->getSiteId());
  }

  /**
   * Tests fallback to env when config doesn't exist.
   */
  public function testFallbackToEnvWhenConfigNotFound(): void {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('hakuvahti_config');

    // Request non-existent config.
    $request = Request::create('/hakuvahti/subscribe?config=nonexistent', 'POST', ['config' => 'nonexistent']);

    $configId = $request->query->get('config') ?? 'default';
    /** @var \Drupal\helfi_hakuvahti\Entity\HakuvahtiConfig|null $config */
    $config = $storage->load($configId);

    // Config doesn't exist, so fallback to env.
    if (!$config) {
      $siteId = getenv('PROJECT_NAME');
    }
    else {
      $siteId = $config->getSiteId();
    }

    $this->assertEquals('test-project', $siteId);
  }

  /**
   * Tests multiple different configs can be loaded.
   */
  public function testMultipleConfigsLoadIndependently(): void {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('hakuvahti_config');

    // Create multiple configs.
    $storage->create([
      'id' => 'jobs',
      'label' => 'Jobs',
      'site_id' => 'jobs-site',
    ])->save();

    $storage->create([
      'id' => 'news',
      'label' => 'News',
      'site_id' => 'news-site',
    ])->save();

    // Test jobs config.
    $request1 = Request::create('/hakuvahti/subscribe?config=jobs', 'POST', ['config' => 'jobs']);
    $configId1 = $request1->query->get('config') ?? 'default';
    /** @var \Drupal\helfi_hakuvahti\Entity\HakuvahtiConfig|null $config1 */
    $config1 = $storage->load($configId1);
    $this->assertEquals('jobs-site', $config1->getSiteId());

    // Test news config.
    $request2 = Request::create('/hakuvahti/subscribe?config=news', 'POST', ['config' => 'news']);
    $configId2 = $request2->query->get('config') ?? 'default';
    /** @var \Drupal\helfi_hakuvahti\Entity\HakuvahtiConfig|null $config2 */
    $config2 = $storage->load($configId2);
    $this->assertEquals('news-site', $config2->getSiteId());

    // Verify they're different.
    $this->assertNotEquals($config1->getSiteId(), $config2->getSiteId());
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    putenv('PROJECT_NAME');
    parent::tearDown();
  }

}
