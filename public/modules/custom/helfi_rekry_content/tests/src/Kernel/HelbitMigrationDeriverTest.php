<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_rekry_content\Kernel;

use Drupal\helfi_rekry_content\Helbit\Settings;
use Drupal\helfi_rekry_content\Plugin\Deriver\HelbitMigrationDeriver;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests migration deriver.
 *
 * @group helfi_rekry_content
 */
class HelbitMigrationDeriverTest extends KernelTestBase {

  private const TEST_HELBIT_KEY = '1234';

  /**
   * Tests Helbit deriver.
   */
  public function testHelbitDeriver(): void {
    $deriver = new HelbitMigrationDeriver(new Settings(self::TEST_HELBIT_KEY));
    $result = $deriver->getDerivativeDefinitions([
      'id' => 'helfi_rekry_jobs',
      'source' => [
        'plugin' => 'helbit_open_jobs',
      ],
    ]);

    $this->assertArrayHasKey('all', $result);
    $this->assertArrayHasKey('changed', $result);
    $this->assertEmpty($result['all']['source']['changed'] ?? NULL);
    $this->assertTrue($result['changed']['source']['changed']);

    $deriver = new HelbitMigrationDeriver(new Settings(self::TEST_HELBIT_KEY));
    $result = $deriver->getDerivativeDefinitions([
      'id' => 'helfi_rekry_task_areas',
      'source' => [
        'plugin' => 'url',
        'url' => 'https://example.com/',
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
        $this->assertStringContainsString(self::TEST_HELBIT_KEY, $url);
      }
    }
  }

}
