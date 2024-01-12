<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_rekry_content\Traits;

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
   * Get http client.
   *
   * @param array $handlers
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
   * @param array $data
   *   Response data.
   *
   * @return \GuzzleHttp\Psr7\Response
   *   Mocked response.
   */
  protected function createMockResponse(array $data): Response {
    return new Response(body: json_encode([
      'status' => 'OK',
    ] + $data));
  }

}
