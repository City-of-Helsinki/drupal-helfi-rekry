<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_rekry_content\Kernel\Plugin\migrate\process;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\helfi_rekry_content\Plugin\migrate\process\ValidateVideoUrl;
use Drupal\media\OEmbed\Provider;
use Drupal\media\OEmbed\ResourceFetcherInterface;
use Drupal\media\OEmbed\UrlResolverInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\Tests\helfi_rekry_content\Kernel\RekryKernelTestBase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the validate_video_url migrate process plugin.
 */
#[RunTestsInSeparateProcesses]
#[Group('helfi_rekry_content')]
class ValidateVideoUrlTest extends RekryKernelTestBase {

  /**
   * Build the plugin under test with prophesized dependencies.
   */
  private function plugin(
    ?UrlResolverInterface $urlResolver = NULL,
    ?ResourceFetcherInterface $resourceFetcher = NULL,
  ): ValidateVideoUrl {
    if (!$urlResolver) {
      $urlResolver = $this->prophesize(UrlResolverInterface::class)->reveal();
    }

    if (!$resourceFetcher) {
      $resourceFetcher = $this->prophesize(ResourceFetcherInterface::class)->reveal();
    }

    return new ValidateVideoUrl(
      [],
      'validate_video_url',
      [],
      $urlResolver,
      $resourceFetcher,
      $this->prophesize(LoggerChannelInterface::class)->reveal(),
    );
  }

  /**
   * Invoke transform() with stub migrate context.
   */
  private function transform(ValidateVideoUrl $plugin, mixed $value): mixed {
    $executable = $this->prophesize(MigrateExecutableInterface::class)->reveal();
    $row = new Row();
    return $plugin->transform($value, $executable, $row, 'field_media_oembed_video');
  }

  /**
   * YouTube short-form URLs are canonicalised before validation.
   */
  #[DataProvider('youtubeUrlVariantsProvider')]
  public function testCanonicalisesYoutubeUrls(string $input): void {
    $canonical = 'https://www.youtube.com/watch?v=g2eYKMjE8ew';
    $resourceUrl = 'https://oembed.example/?url=' . $canonical;
    $provider = new Provider('YouTube', 'https://youtube.com', [
      ['url' => 'https://www.youtube.com/oembed'],
    ]);

    $urlResolver = $this->prophesize(UrlResolverInterface::class);
    $urlResolver->getProviderByUrl($canonical)->willReturn($provider);
    $urlResolver->getResourceUrl($canonical)->willReturn($resourceUrl);

    $resourceFetcher = $this->prophesize(ResourceFetcherInterface::class);
    $resourceFetcher->fetchResource($resourceUrl)->shouldBeCalled();

    $plugin = $this->plugin($urlResolver->reveal(), $resourceFetcher->reveal());

    $this->assertSame($canonical, $this->transform($plugin, $input));
  }

  /**
   * Data provider for testCanonicalisesYoutubeUrls().
   *
   * @return array<string, array{string}>
   *   Map of case label to single-element argument list for the test.
   */
  public static function youtubeUrlVariantsProvider(): array {
    return [
      'no scheme, watch'   => ['youtube.com/watch?v=g2eYKMjE8ew'],
      'short youtu.be'     => ['youtu.be/g2eYKMjE8ew'],
      'short with ?v='     => ['youtu.be/?v=g2eYKMjE8ew'],
      'no scheme, embed'   => ['https://youtube.com/embed/g2eYKMjE8ew'],
      'no www'             => ['https://youtube.com/watch?v=g2eYKMjE8ew'],
      'canonical passthru' => ['https://www.youtube.com/watch?v=g2eYKMjE8ew'],
      'extra query params' => ['https://www.youtube.com/watch?foo=bar&v=g2eYKMjE8ew&bar=foo'],
    ];
  }

}
