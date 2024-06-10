<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_rekry_content\Unit;

use Drupal\helfi_rekry_content\Helbit\Settings;
use Drupal\helfi_rekry_content\Plugin\Deriver\HelbitMigrationDeriver;
use Drupal\Tests\UnitTestCase;

/**
 * @group helfi_rekry_content
 */
class HelbitDeriverTest extends UnitTestCase {

  /**
   * Tests Helbit deriver.
   *
   * @return void
   */
  public function testHelbitDeriver(): void {
    $deriver = new HelbitMigrationDeriver(new Settings('123'));
    $result = $deriver->getDerivativeDefinitions([
      'id' => 'helfi_rekry_jobs',
      'source' => [
        'plugin' => 'helbit_open_jobs',
      ],
    ]);

    $result = $deriver->getDerivativeDefinitions([
      'id' => 'helfi_rekry_task_areas',
      'source' => [
        'plugin' => 'url',
        'url' => 'https://helbit.fi/portal-api/recruitment/v2.3/params/hierarchy/tasks',
      ],
    ]);

  }

}
