<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_rekry_content\Kernel;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\helfi_hakuvahti\DrupalSettings;
use Drupal\helfi_rekry_content\Hook\JobListingTheme;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\paragraphs\Entity\Paragraph;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Tests JobListingTheme hooks.
 */
#[Group('helfi_rekry_content')]
#[RunTestsInSeparateProcesses]
class JobListingThemeTest extends RekryKernelTestBase {

  use ProphecyTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
    'text',
    'filter',
    'language',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['helfi_hakuvahti', 'filter']);

    NodeType::create(['type' => 'page', 'name' => 'Page'])->save();
  }

  /**
   * Gets the system under test with real container services.
   */
  private function getSut(): JobListingTheme {
    return new JobListingTheme(
      $this->container->get(LanguageManagerInterface::class),
      $this->container->get(EntityTypeManagerInterface::class),
      $this->container->get(DrupalSettings::class),
    );
  }

  /**
   * Builds a mock job_search paragraph pointing to the given node ID.
   */
  private function mockJobSearchParagraph(string $nid): Paragraph {
    $field = $this->prophesize(FieldItemListInterface::class);
    $field->getString()->willReturn($nid);

    $paragraph = $this->prophesize(Paragraph::class);
    $paragraph->getType()->willReturn('job_search');
    $paragraph->get('field_job_search_result_page')->willReturn($field->reveal());

    return $paragraph->reveal();
  }

  /**
   * Tests that the result page URL is attached for a job_search paragraph.
   */
  public function testResultsPageUrlIsAttached(): void {
    $node = Node::create(['type' => 'page', 'title' => 'Jobs']);
    $node->save();

    $variables = ['paragraph' => $this->mockJobSearchParagraph((string) $node->id())];

    $this->getSut()->preprocessParagraph($variables);

    $this->assertArrayHasKey(
      'results_page_path',
      $variables['#attached']['drupalSettings']['helfi_rekry_job_search'],
    );
    $this->assertNotEmpty(
      $variables['#attached']['drupalSettings']['helfi_rekry_job_search']['results_page_path'],
    );
  }

}
