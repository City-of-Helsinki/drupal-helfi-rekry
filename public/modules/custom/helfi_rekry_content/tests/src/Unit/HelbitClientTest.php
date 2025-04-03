<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_rekry_content\Unit;

use Drupal\helfi_rekry_content\Helbit\HelbitClient;
use Drupal\helfi_rekry_content\Helbit\HelbitEnvironment;
use Drupal\helfi_rekry_content\Helbit\Settings;
use Drupal\Tests\helfi_rekry_content\Traits\HelbitTestTrait;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;

/**
 * Tests helbit client.
 *
 * @group helfi_rekry_content
 */
class HelbitClientTest extends UnitTestCase {

  use ProphecyTrait;
  use HelbitTestTrait;

  /**
   * Gets the SUT.
   *
   * @return \Drupal\helfi_rekry_content\Helbit\HelbitClient
   *   The Helbit client.
   */
  private function getSut(ClientInterface $client): HelbitClient {
    $logger = $this->prophesize(LoggerInterface::class)->reveal();
    $settings = new Settings([
      new HelbitEnvironment('123', 'https://example.com'),
    ]);

    return new HelbitClient($logger, $client, $settings);
  }

  /**
   * Assert that response parsed response is returned.
   */
  public function testJobListingResponse(): void {
    $expected = [
      [
        'job' => 'advert',
        'baseUrl' => 'https://example.com',
      ],
    ];

    $client = $this->createMockHttpClient([
      $this->createMockResponse([
        'jobAdvertisements' => [
          [
            'job' => 'advert',
          ],
        ],
      ]),
    ]);

    $response = $this->getSut($client)->getJobListings('fi');

    $this->assertEquals($expected, $response);
  }

  /**
   * Assert requests query parameters.
   */
  public function testRequestQueryParameters(): void {
    $client = $this->createMockHttpClient([
      function (Request $request) {
        parse_str($request->getUri()->getQuery(), $result);

        $this->assertEquals('123', $result['client'] ?? NULL);
        $this->assertEquals('fi_FI', $result['lang'] ?? NULL);

        return $this->createMockResponse([]);
      },
    ]);

    $this->getSut($client)->getJobListings('fi');
  }

}
