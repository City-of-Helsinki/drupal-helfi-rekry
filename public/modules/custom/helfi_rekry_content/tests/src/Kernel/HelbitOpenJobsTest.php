<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_rekry_content\Kernel;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\helfi_rekry_content\Helbit\HelbitClient;
use Drupal\helfi_rekry_content\Plugin\migrate\source\HelbitOpenJobs;
use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Tests migration source plugin.
 *
 * @group helfi_rekry_content
 */
class HelbitOpenJobsTest extends KernelTestBase {

  use ProphecyTrait;

  /**
   * Tests that exception is thrown when plugin is missing configuration.
   */
  public function testException(): void {
    $helbit = $this->prophesize(HelbitClient::class);
    $helbit
      ->getJobListings(Argument::any(), Argument::any())
      ->willReturn([
        [
          'data' => '123',
        ],
      ]);

    // Ids field is configured incorrectly.
    $this->expectException(\InvalidArgumentException::class);

    $this
      ->getSut($helbit->reveal(), [
        'fields' => [
          [
            'name' => 'id',
            'selector' => 'data',
          ],
        ],
      ])
      ->next();
  }

  /**
   * Tests iterator.
   */
  public function testIterator(): void {
    $helbit = $this->prophesize(HelbitClient::class);
    $helbit
      ->getJobListings(Argument::any(), Argument::any())
      ->willReturn([
        [
          'nested_data' => [
            'field' => 'response1',
          ],
        ],
        [
          // This row should be skipped.
          'some_other_field' => 'response2',
        ],
        [
          'some_other_field' => 'response3',
          'nested_data' => [
            'field' => 'response3',
          ],
        ],
      ]);

    $sut = $this
      ->getSut($helbit->reveal(), [
        'fields' => [
          [
            'name' => 'my_field',
            'selector' => 'nested_data/field',
          ],
        ],
        'ids' => [
          'my_field' => [
            'type' => 'string',
          ],
        ],
      ]);

    $results = [];

    foreach ($sut as $row) {
      $source = $row->getSource();

      $this->assertArrayNotHasKey('some_other_field', $source);
      $this->assertStringContainsString('response', $source['my_field']);
      $this->assertTrue(in_array($source['langcode'] ?? NULL, ['fi', 'sv', 'en']));
      $results[] = $source;
    }

    $this->assertCount(2 * 3, $results);
  }

  /**
   * Gets service under test.
   */
  private function getSut(HelbitClient $helbit, array $configuration): HelbitOpenJobs {
    $container = new ContainerBuilder();
    $idMap = $this->prophesize(MigrateIdMapInterface::class);
    $migration = $this->prophesize(MigrationInterface::class);
    $migration->getIdMap()->willReturn($idMap->reveal());
    $migration->id()->willReturn('test_migration');

    $container->set(HelbitClient::class, $helbit);

    return HelbitOpenJobs::create($container, $configuration, 'test_plugin', [], $migration->reveal());
  }

}
