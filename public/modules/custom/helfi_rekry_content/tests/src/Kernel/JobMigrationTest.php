<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_rekry_content\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\media\OEmbed\Provider;
use Drupal\media\OEmbed\ProviderException;
use Drupal\media\OEmbed\UrlResolverInterface;
use Drupal\migrate\MigrateSkipRowException;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Job migration tests.
 */
class JobMigrationTest extends KernelTestBase {

  use ProphecyTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'helfi_rekry_content',
  ];

  /**
   * Test video URL sanitization.
   *
   * @dataProvider videoUrlData
   */
  public function testVideoUrlSanitization(mixed $expected, array $videoUrls): void {
    foreach ($videoUrls as $videoUrl) {
      $this->assertEquals($expected, \_helfi_rekry_content_sanitize_video_url($videoUrl));
    }
  }

  /**
   * Test video URL validation.
   */
  public function testVideoValidationExceptions(): void {
    $urlResolver = $this->prophesize(UrlResolverInterface::class);
    $urlResolver->getProviderByUrl(Argument::any())
      ->willThrow(ProviderException::class);

    $this->container->set(UrlResolverInterface::class, $urlResolver->reveal());

    $this->expectException(MigrateSkipRowException::class);
    _helfi_rekry_content_get_video_url('some-url');
  }

  /**
   * Test video URL validation with unknown provider.
   */
  public function testVideoValidationProvider(): void {
    $provider = new Provider('Some provider', 'https://example.com', [
      ['url' => 'https://example.com/oembed'],
    ]);

    $urlResolver = $this->prophesize(UrlResolverInterface::class);
    $urlResolver->getProviderByUrl(Argument::any())
      ->willReturn($provider);

    $this->container->set(UrlResolverInterface::class, $urlResolver->reveal());

    $this->expectException(MigrateSkipRowException::class);
    _helfi_rekry_content_get_video_url('some-url');
  }

  /**
   * Data provider for testVideoUrlSanitization().
   */
  public static function videoUrlData(): array {
    return [
      ['', ['    ']],
      [
        'https://www.youtube.com/watch?v=g2eYKMjE8ew',
        [
          'youtube.com/watch?v=g2eYKMjE8ew',
          'youtu.be/g2eYKMjE8ew',
          'youtu.be/?v=g2eYKMjE8ew',
          'https://youtube.com/embed/g2eYKMjE8ew',
          'https://youtube.com/watch?v=g2eYKMjE8ew',
          'https://www.youtube.com/watch?v=g2eYKMjE8ew',
          'https://www.youtube.com/watch?foo=bar&v=g2eYKMjE8ew&bar=foo',
        ],
      ],
    ];
  }

}
