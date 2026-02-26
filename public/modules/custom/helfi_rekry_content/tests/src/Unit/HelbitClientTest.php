<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_rekry_content\Unit;

use Drupal\helfi_rekry_content\Helbit\HelbitClient;
use Drupal\helfi_rekry_content\Helbit\HelbitEnvironment;
use Drupal\helfi_rekry_content\Helbit\HelbitException;
use Drupal\helfi_rekry_content\Helbit\Settings;
use Drupal\Tests\helfi_rekry_content\Traits\HelbitTestTrait;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\Group;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Tests helbit client.
 */
#[Group('helfi_rekry_content')]
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
    $settings = new Settings([
      new HelbitEnvironment('123', 'https://example.com'),
    ]);

    return new HelbitClient($client, $settings);
  }

  /**
   * Assert that an exception is thrown when response is fails.
   */
  public function testJobListingErrors(): void {
    $client = $this->createMockHttpClient([
      new BadResponseException('fail', new Request('GET', 'https://example.com'), new Response(418)),
    ]);

    $this->expectException(HelbitException::class);
    $this->getSut($client)->getJobListings('fi');
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
