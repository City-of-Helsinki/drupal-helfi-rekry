<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_hakuvahti\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests for Hakuvahti install and update hooks.
 *
 * @group helfi_hakuvahti
 */
class HakuvahtiInstallTest extends KernelTestBase {

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

    // Load install file once for all tests.
    require_once dirname(__DIR__, 3) . '/helfi_hakuvahti.install';
  }

  /**
   * Tests that hook_install() creates default config with PROJECT_NAME.
   */
  public function testInstallCreatesDefaultConfig(): void {
    // Set PROJECT_NAME environment variable.
    putenv('PROJECT_NAME=test-project');

    // Run hook_install().
    helfi_hakuvahti_install();

    // Verify default config was created.
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('hakuvahti_config');

    /** @var \Drupal\helfi_hakuvahti\Entity\HakuvahtiConfig|null $default */
    $default = $storage->load('default');

    $this->assertNotNull($default, 'Default config should be created');
    $this->assertEquals('default', $default->id());
    $this->assertEquals('Default', $default->label());
    $this->assertEquals('test-project', $default->getSiteId());

    // Cleanup.
    putenv('PROJECT_NAME');
  }

  /**
   * Tests that hook_install() doesn't create config without PROJECT_NAME.
   */
  public function testInstallWithoutProjectName(): void {
    // Ensure PROJECT_NAME is not set.
    putenv('PROJECT_NAME');

    // Run hook_install().
    helfi_hakuvahti_install();

    // Verify no config was created.
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('hakuvahti_config');

    /** @var \Drupal\helfi_hakuvahti\Entity\HakuvahtiConfig|null $default */
    $default = $storage->load('default');

    $this->assertNull($default, 'Default config should not be created without PROJECT_NAME');
  }

  /**
   * Tests that hook_install() doesn't overwrite existing config.
   */
  public function testInstallDoesNotOverwriteExistingConfig(): void {
    putenv('PROJECT_NAME=original-project');

    // Create a default config manually first.
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('hakuvahti_config');

    $storage->create([
      'id' => 'default',
      'label' => 'Custom Default',
      'site_id' => 'custom-site-id',
    ])->save();

    // Run hook_install().
    helfi_hakuvahti_install();

    // Verify original config is unchanged.
    /** @var \Drupal\helfi_hakuvahti\Entity\HakuvahtiConfig|null $default */
    $default = $storage->load('default');

    $this->assertNotNull($default);
    $this->assertEquals('Custom Default', $default->label(), 'Existing config should not be overwritten');
    $this->assertEquals('custom-site-id', $default->getSiteId(), 'Existing site_id should not be overwritten');

    // Cleanup.
    putenv('PROJECT_NAME');
  }

  /**
   * Tests that update hook 9001 creates default config.
   */
  public function testUpdateHookCreatesDefaultConfig(): void {
    putenv('PROJECT_NAME=update-project');

    // Run update hook.
    helfi_hakuvahti_update_9001();

    // Verify default config was created.
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('hakuvahti_config');

    /** @var \Drupal\helfi_hakuvahti\Entity\HakuvahtiConfig|null $default */
    $default = $storage->load('default');

    $this->assertNotNull($default, 'Update hook should create default config');
    $this->assertEquals('default', $default->id());
    $this->assertEquals('Default', $default->label());
    $this->assertEquals('update-project', $default->getSiteId());

    // Cleanup.
    putenv('PROJECT_NAME');
  }

  /**
   * Tests that update hook doesn't overwrite existing config.
   */
  public function testUpdateHookDoesNotOverwriteExistingConfig(): void {
    putenv('PROJECT_NAME=new-project');

    $storage = $this->container->get('entity_type.manager')
      ->getStorage('hakuvahti_config');

    // Create existing default config.
    $storage->create([
      'id' => 'default',
      'label' => 'Existing Default',
      'site_id' => 'existing-site',
    ])->save();

    // Run update hook.
    helfi_hakuvahti_update_9001();

    // Verify existing config is unchanged.
    /** @var \Drupal\helfi_hakuvahti\Entity\HakuvahtiConfig|null $default */
    $default = $storage->load('default');

    $this->assertNotNull($default);
    $this->assertEquals('Existing Default', $default->label(), 'Update hook should not overwrite existing config');
    $this->assertEquals('existing-site', $default->getSiteId(), 'Update hook should not overwrite existing site_id');

    // Cleanup.
    putenv('PROJECT_NAME');
  }

  /**
   * Tests that update hook 9001 doesn't create config without PROJECT_NAME.
   */
  public function testUpdateHookWithoutProjectName(): void {
    // Ensure PROJECT_NAME is not set.
    putenv('PROJECT_NAME');

    // Run update hook.
    helfi_hakuvahti_update_9001();

    // Verify no config was created.
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('hakuvahti_config');

    /** @var \Drupal\helfi_hakuvahti\Entity\HakuvahtiConfig|null $default */
    $default = $storage->load('default');

    $this->assertNull($default, 'Update hook should not create config without PROJECT_NAME');
  }

  /**
   * Tests that running update hook multiple times is safe.
   */
  public function testUpdateHookMultipleRuns(): void {
    putenv('PROJECT_NAME=multi-project');

    // Run update hook first time.
    helfi_hakuvahti_update_9001();

    $storage = $this->container->get('entity_type.manager')
      ->getStorage('hakuvahti_config');

    /** @var \Drupal\helfi_hakuvahti\Entity\HakuvahtiConfig|null $first */
    $first = $storage->load('default');
    $this->assertNotNull($first);
    $firstSiteId = $first->getSiteId();

    // Run update hook second time.
    helfi_hakuvahti_update_9001();

    /** @var \Drupal\helfi_hakuvahti\Entity\HakuvahtiConfig|null $second */
    $second = $storage->load('default');
    $this->assertNotNull($second);
    $this->assertEquals($firstSiteId, $second->getSiteId(), 'Multiple runs should not change config');

    // Verify only one config exists (no duplicates).
    $allConfigs = $storage->loadMultiple();
    $this->assertCount(1, $allConfigs, 'Should only have one default config');

    // Cleanup.
    putenv('PROJECT_NAME');
  }

  /**
   * Tests that install and update hooks create identical configs.
   */
  public function testInstallAndUpdateHookConsistency(): void {
    putenv('PROJECT_NAME=consistency-test');

    $storage = $this->container->get('entity_type.manager')
      ->getStorage('hakuvahti_config');

    // Test install hook.
    helfi_hakuvahti_install();
    /** @var \Drupal\helfi_hakuvahti\Entity\HakuvahtiConfig|null $installConfig */
    $installConfig = $storage->load('default');
    $installConfig->delete();

    // Test update hook.
    helfi_hakuvahti_update_9001();
    /** @var \Drupal\helfi_hakuvahti\Entity\HakuvahtiConfig|null $updateConfig */
    $updateConfig = $storage->load('default');

    // Both should create identical configs.
    $this->assertEquals('default', $updateConfig->id());
    $this->assertEquals('Default', $updateConfig->label());
    $this->assertEquals('consistency-test', $updateConfig->getSiteId());

    // Cleanup.
    putenv('PROJECT_NAME');
  }

}
