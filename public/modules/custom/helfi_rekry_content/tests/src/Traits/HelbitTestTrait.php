<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_rekry_content\Traits;

use Drupal\helfi_rekry_content\Helbit\HelbitClient;
use Drupal\Tests\helfi_api_base\Traits\ApiTestTrait;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

/**
 * Provides shared functionality for tests.
 */
trait HelbitTestTrait {

  use ApiTestTrait {
    // Api base does not allow passing callables to createMockHttpClient.
    createMockHttpClient as apiBaseCreateMockHttpClient;
  }

  /**
   * Replaces the Helbit client.
   *
   * This method mocks the service directly. getJobListings of this mock
   * can be called however many times, unlike the Guzzle mock, that only returns
   * the exact number of configured responses.
   *
   * @param array<string, mixed> $jobListings
   *   Helbit response elements.
   */
  protected function mockHelbitClient(array $jobListings): void {
    $client = $this->createMock(HelbitClient::class);
    $client->method('getJobListings')
      ->willReturnCallback(static fn (string $language): array => $jobListings[$language] ?? []);
    $this->container->set(HelbitClient::class, $client);
  }

  /**
   * Builds a single Helbit job listing source element.
   *
   * @return array<string, mixed>
   *   A Helbit response element as returned by getJobListings().
   */
  protected function jobRow(string $id, string $title, string $address): array {
    return [
      'jobAdvertisement' => [
        'id' => $id,
        'title' => $title,
        'address' => $address,
      ],
    ];
  }

  /**
   * Get http client.
   *
   * @param array<int, mixed> $handlers
   *   Request handlers.
   *
   * @return \GuzzleHttp\ClientInterface
   *   The client interface.
   */
  protected function createMockHttpClient(array $handlers): ClientInterface {
    $mock = new MockHandler($handlers);
    $handlerStack = HandlerStack::create($mock);
    return new Client(['handler' => $handlerStack]);
  }

  /**
   * Json encode response.
   *
   * @param array<string, mixed> $data
   *   Response data.
   *
   * @return \GuzzleHttp\Psr7\Response
   *   Mocked response.
   */
  protected function createMockResponse(array $data): Response {
    return new Response(body: json_encode([
      'status' => 'OK',
    ] + $data, JSON_THROW_ON_ERROR));
  }

}
