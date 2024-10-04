<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_google_api\Kernel;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\helfi_google_api\JobIndexingService;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\Tests\helfi_api_base\Functional\ExistingSiteTestBase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Tests file name transliteration.
 *
 * @group helfi_rekry_content
 */
class IndexingTest extends ExistingSiteTestBase {
  use ProphecyTrait;

  public static $modules = ['helfi_google_api'];

  /**
   * Test the indexing.
   */
  public function testIndexingJoblisting(): void {
    $recruitmentId = 'TESTI-1234-56-7890';
    $timestamp = time() - 1;

    $node = $this->createNode([
      'type' => 'job_listing',
      'langcode' => 'sv',
      'title' => 'en jobb',
      'field_recruitment_id' => $recruitmentId,
      'publish_on' => $timestamp,
    ]);

    /** @var \Drupal\helfi_google_api\JobIndexingService $indexingService */
    $indexingService = $this->getSut([$node]);

    /** @var \Drupal\helfi_google_api\Response $response */
    $response = $indexingService->indexEntity($node);

    $this->assertTrue($response->isDryRun());
    $this->assertCount(1, $response->getUrls());

    // Test that the alias was created.
    $expected = $node->toUrl()->toString() . '-';
    $indexed_url = $response->getUrls()[0];
    $this->assertTrue(str_contains($indexed_url, $expected));
  }

  /**
   * The system under test.
   *
   * @return JobIndexingService
   *   The job indexing service.
   */
  private function getSut(): JobIndexingService {
    $googleApi = $this->container->get('Drupal\helfi_google_api\GoogleApi');
    $entityTypeManager = $this->container->get(EntityTypeManagerInterface::class);
    $aliasManager = $this->container->get(AliasManagerInterface::class);
    $urlGenerator = $this->container->get(UrlGeneratorInterface::class);
    $logger = $this->container->get('logger.channel.helfi_google_api');

    return new JobIndexingService(
      $googleApi,
      $entityTypeManager,
      $aliasManager,
      $urlGenerator,
      $logger
    );
  }

}
