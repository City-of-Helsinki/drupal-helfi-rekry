<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_rekry_content\Kernel;

use Drupal\helfi_rekry_content\Service\HakuvahtiTracker;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermStorage;
use Drupal\Tests\helfi_api_base\Traits\ApiTestTrait;
use Drupal\Tests\helfi_media\Kernel\HelfiMediaKernelTestBase;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\Loader\Configurator\Traits\PropertyTrait;

/**
 * Tests file name transliteration.
 */
#[RunTestsInSeparateProcesses]
#[Group('helfi_rekry_content')]
class HakuvahtiSubscribeTest extends HelfiMediaKernelTestBase {

  use ApiTestTrait;
  use PropertyTrait;
  use TaxonomyTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE; // phpcs:ignore

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'text',
    'node',
    'taxonomy',
    'options',
    'readonly_field_widget',
    'helfi_hakuvahti',
    'helfi_rekry_content',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('node');
    $this->installConfig(['helfi_media', 'field', 'taxonomy', 'node', 'helfi_rekry_content']);
    $this->installSchema('helfi_rekry_content', ['hakuvahti_selected_filters']);

    Term::create([
      'tid' => 90,
      'vid' => 'employment',
      'langcode' => 'en',
      'name' => 'Permanent public service employment',
    ])->save();

    Term::create([
      'tid' => 91,
      'vid' => 'employment',
      'langcode' => 'en',
      'name' => 'Permanent contractual employment',
    ])->save();

    $term = Term::create([
      'tid' => 999,
      'vid' => 'task_area',
      'langcode' => 'en',
      'name' => 'Practical nurses',
      'field_external_id' => 33,
    ]);
    $term->save();
  }

  /**
   * Test the hakuvahti controller.
   */
  public function testSubscriptionController(): void {
    $requestData = file_get_contents(__DIR__ . "/../../fixtures/subscribe_request.json");

    $data = json_decode($requestData, TRUE);
    $data['search_description'] = '-';

    $storage = $this->prophesize(TermStorage::class);
    $storage->loadByProperties(Argument::any())->willReturn($data);
    $storage->reveal();

    /** @var \Drupal\helfi_rekry_content\Service\HakuvahtiTracker $tracker */
    $tracker = $this->container->get(HakuvahtiTracker::class);

    $filters = $tracker->parseQuery($data['elastic_query'], $data['query']);
    foreach ($filters as $filter) {
      $this->assertNotEmpty($filter);
    }

    $result = $tracker->saveSelectedFilters($filters);
    $this->assertTrue($result);
  }

}
