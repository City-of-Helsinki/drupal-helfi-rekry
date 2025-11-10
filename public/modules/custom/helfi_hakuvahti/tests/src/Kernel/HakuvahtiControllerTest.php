<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_hakuvahti\Kernel;

use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\helfi_api_base\Traits\ApiTestTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\Traits\PropertyTrait;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Tests for hakuvahti controller.
 */
class HakuvahtiControllerTest extends KernelTestBase {

  use ApiTestTrait;
  use UserCreationTrait;
  use PropertyTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'system',
    'helfi_hakuvahti',
  ];

  /**
   * Tests handleConfirmFormSubmission.
   */
  public function testHandleConfirmFormSubmission(): void {
    $this->setupMockHttpClient([
      new Response(200, body: 'success'),
      new Response(404, body: 'not found'),
      new Response(500, body: 'fail'),
      new RequestException("womp womp", new Request('POST', 'test')),
    ]);

    $this
      ->config('helfi_hakuvahti.settings')
      ->set('base_url', 'https://example.com')
      ->save();

    $this->setUpCurrentUser(permissions: ['access content']);

    $logger = $this->prophesize(LoggerInterface::class);
    $this->container->set('logger.channel.helfi_hakuvahti', $logger->reveal());

    // Get request.
    $response = $this->makeRequest('GET', 'helfi_hakuvahti.confirm', ['hash' => 'a', 'subscription' => 'b']);
    $this->assertEquals(200, $response->getStatusCode());
    $this->assertStringContainsString('Confirm saved search', $response->getContent() ?? '');

    // Success.
    $response = $this->makeRequest('POST', 'helfi_hakuvahti.confirm', ['hash' => 'a', 'subscription' => 'b']);
    $this->assertEquals(200, $response->getStatusCode());
    $this->assertStringContainsString('Search saved successfully', $response->getContent() ?? '');

    // Not found.
    $response = $this->makeRequest('POST', 'helfi_hakuvahti.confirm', ['hash' => 'a', 'subscription' => 'b']);
    $this->assertEquals(200, $response->getStatusCode());
    $this->assertStringContainsString('Confirmation failed', $response->getContent() ?? '');

    // Server error.
    $response = $this->makeRequest('POST', 'helfi_hakuvahti.confirm', ['hash' => 'a', 'subscription' => 'b']);
    $this->assertEquals(200, $response->getStatusCode());
    $this->assertStringContainsString('Confirmation failed', $response->getContent() ?? '');

    // Guzzle exception.
    $response = $this->makeRequest('POST', 'helfi_hakuvahti.confirm', ['hash' => 'a', 'subscription' => 'b']);
    $this->assertEquals(200, $response->getStatusCode());
    $this->assertStringContainsString('Confirmation failed', $response->getContent() ?? '');
  }

  /**
   * Process a request.
   *
   * @param string $method
   *   HTTP method.
   * @param string $route
   *   Drupal route.
   * @param array $query
   *   Query parameters.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Controller response.
   */
  private function makeRequest(string $method, string $route, array $query = []): SymfonyResponse {
    $url = Url::fromRoute($route, options: [
      'query' => $query,
    ]);

    $request = $this->getMockedRequest($url->toString(), $method);

    return $this->processRequest($request);
  }

}
