<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_hakuvahti\Kernel;

use Drupal\helfi_api_base\Environment\EnvironmentEnum;
use Drupal\helfi_api_base\Environment\Project;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\helfi_api_base\Traits\EnvironmentResolverTrait;

/**
 * Tests install and update hooks for helfi_hakuvahti module.
 *
 * @group helfi_hakuvahti
 */
class HakuvahtiInstallTest extends KernelTestBase {

  use EnvironmentResolverTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'helfi_hakuvahti',
    'helfi_api_base',
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
   * Helper to mock EnvironmentResolver with a project name.
   *
   * @param string $projectName
   *   The project name to set.
   */
  protected function mockEnvironmentResolver(string $projectName): void {
    $this->container->set(
      'helfi_api_base.environment_resolver',
      $this->getEnvironmentResolver($projectName, EnvironmentEnum::Local)
    );
  }

  /**
   * Tests that hook_install() populates site_id from EnvironmentResolver.
   */
  public function testInstallPopulatesSiteId(): void {
    // Mock EnvironmentResolver.
    $this->mockEnvironmentResolver(Project::REKRY);

    // Run hook_install().
    helfi_hakuvahti_install();

    // Verify site_id was populated in config.
    $config = $this->config('helfi_hakuvahti.config.default');

    $this->assertEquals('default', $config->get('id'));
    $this->assertEquals('Default', $config->get('label'));
    $this->assertEquals(Project::REKRY, $config->get('site_id'));
  }

  /**
   * Tests hook_install() without EnvironmentResolver throws error.
   */
  public function testInstallWithoutProjectName(): void {
    // Don't mock EnvironmentResolver - should throw exception.
    $this->expectException(\Exception::class);

    // Run hook_install().
    helfi_hakuvahti_install();
  }

  /**
   * Tests that hook_install() overwrites site_id in existing config.
   */
  public function testInstallOverwritesSiteId(): void {
    // Mock EnvironmentResolver.
    $this->mockEnvironmentResolver(Project::REKRY);

    // Manually set a different site_id first.
    \Drupal::configFactory()
      ->getEditable('helfi_hakuvahti.config.default')
      ->set('site_id', 'old-value')
      ->save();

    // Run hook_install().
    helfi_hakuvahti_install();

    // Verify site_id was updated.
    $config = $this->config('helfi_hakuvahti.config.default');
    $this->assertEquals(Project::REKRY, $config->get('site_id'), 'site_id should be updated from environment');
  }

  /**
   * Tests that update hook 9001 creates default config.
   */
  public function testUpdateHookCreatesDefaultConfig(): void {
    // Mock EnvironmentResolver.
    $this->mockEnvironmentResolver(Project::REKRY);

    // Clear the config first to simulate upgrade scenario.
    \Drupal::configFactory()
      ->getEditable('helfi_hakuvahti.config.default')
      ->delete();

    // Run update hook.
    helfi_hakuvahti_update_9001();

    // Verify config was created.
    $config = $this->config('helfi_hakuvahti.config.default');
    $this->assertEquals('default', $config->get('id'));
    $this->assertEquals('Default', $config->get('label'));
    $this->assertEquals(Project::REKRY, $config->get('site_id'));
  }

  /**
   * Tests that update hook doesn't overwrite existing site_id.
   */
  public function testUpdateHookDoesNotOverwriteExistingSiteId(): void {
    // Mock EnvironmentResolver.
    $this->mockEnvironmentResolver(Project::REKRY);

    // Set existing site_id.
    \Drupal::configFactory()
      ->getEditable('helfi_hakuvahti.config.default')
      ->set('site_id', 'existing-site')
      ->save();

    // Run update hook.
    helfi_hakuvahti_update_9001();

    // Verify existing site_id is unchanged.
    $config = $this->config('helfi_hakuvahti.config.default');
    $this->assertEquals('existing-site', $config->get('site_id'), 'Update hook should not overwrite existing site_id');
  }

  /**
   * Tests update hook 9001 without EnvironmentResolver throws error.
   */
  public function testUpdateHookWithoutProjectName(): void {
    // Don't mock EnvironmentResolver - should throw exception.
    $this->expectException(\Exception::class);

    // Run update hook.
    helfi_hakuvahti_update_9001();
  }

  /**
   * Tests that running update hook multiple times is safe (idempotent).
   */
  public function testUpdateHookIsIdempotent(): void {
    // Mock EnvironmentResolver.
    $this->mockEnvironmentResolver(Project::REKRY);

    // Clear config first.
    \Drupal::configFactory()
      ->getEditable('helfi_hakuvahti.config.default')
      ->delete();

    // Run update hook first time.
    helfi_hakuvahti_update_9001();
    $firstSiteId = $this->config('helfi_hakuvahti.config.default')->get('site_id');

    // Run update hook second time.
    helfi_hakuvahti_update_9001();
    $secondSiteId = $this->config('helfi_hakuvahti.config.default')->get('site_id');

    $this->assertEquals($firstSiteId, $secondSiteId, 'Multiple runs should not change config');
  }

  /**
   * Tests that install and update hooks set site_id consistently.
   */
  public function testInstallAndUpdateHookConsistency(): void {
    // Mock EnvironmentResolver.
    $this->mockEnvironmentResolver(Project::REKRY);

    // Test install hook.
    helfi_hakuvahti_install();
    $installSiteId = $this->config('helfi_hakuvahti.config.default')->get('site_id');

    // Clear and test update hook.
    \Drupal::configFactory()
      ->getEditable('helfi_hakuvahti.config.default')
      ->delete();
    helfi_hakuvahti_update_9001();
    $updateSiteId = $this->config('helfi_hakuvahti.config.default')->get('site_id');

    // Both should set the same site_id.
    $this->assertEquals($installSiteId, $updateSiteId, 'Install and update hooks should set identical site_id');
  }

}
