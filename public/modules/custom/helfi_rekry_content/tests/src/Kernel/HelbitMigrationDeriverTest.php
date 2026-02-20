<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_rekry_content\Kernel;

use Drupal\helfi_rekry_content\Helbit\HelbitEnvironment;
use Drupal\helfi_rekry_content\Helbit\Settings;
use Drupal\helfi_rekry_content\Plugin\Deriver\HelbitMigrationDeriver;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests migration deriver.
 */
#[RunTestsInSeparateProcesses]
#[Group('helfi_rekry_content')]
class HelbitMigrationDeriverTest extends KernelTestBase {

  private const TEST_HELBIT_KEY = '1234';

  /**
   * Tests Helbit deriver.
   */
  public function testHelbitDeriver(): void {
    $settings = new Settings([new HelbitEnvironment(self::TEST_HELBIT_KEY, 'https://example.com')]);
    $deriver = new HelbitMigrationDeriver($settings);
    $result = $deriver->getDerivativeDefinitions([
      'id' => 'helfi_rekry_task_areas',
      'source' => [
        'plugin' => 'url',
        'url' => '/foobar',
      ],
    ]);

    foreach (['fi', 'sv', 'en'] as $langcode) {
      $this->assertArrayHasKey($langcode, $result);
      $this->assertEqualsCanonicalizing([
        'plugin' => 'default_value',
        'default_value' => $langcode,
      ], $result[$langcode]['process']['langcode']);

      $this->assertNotEmpty($result[$langcode]['source']['urls'] ?? []);
      foreach ($result[$langcode]['source']['urls'] as $url) {
        $this->assertStringContainsString($langcode, $url);
        $this->assertStringContainsString('https://example.com/foobar', $url);
        $this->assertStringContainsString(self::TEST_HELBIT_KEY, $url);
      }
    }
  }

}
