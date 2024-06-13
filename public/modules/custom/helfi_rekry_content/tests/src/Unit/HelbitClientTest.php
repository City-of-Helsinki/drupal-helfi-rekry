<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_rekry_content\Unit;

use Drupal\helfi_rekry_content\Helbit\HelbitClient;
use Drupal\helfi_rekry_content\Helbit\Settings;
use Drupal\Tests\helfi_rekry_content\Traits\HelbitTestTrait;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \Drupal\helfi_rekry_content\Helbit\HelbitClient
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

    return new HelbitClient($logger, $client, new Settings('123'));
  }

  /**
   * Assert that response parsed response is returned.
   *
   * @covers ::getJobListings
   * @covers ::getHelbitLangcode
   * @covers ::makeRequest
   */
  public function testJobListingResponse(): void {
    $expected = [
      'test-data',
    ];

    $client = $this->createMockHttpClient([
      $this->createMockResponse([
        'jobAdvertisements' => $expected,
      ]),
    ]);

    $response = $this->getSut($client)->getJobListings('fi');

    $this->assertEquals($expected, $response);
  }

  /**
   * Assert requests query parameters.
   *
   * @covers ::getJobListings
   * @covers ::getHelbitLangcode
   * @covers ::makeRequest
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
