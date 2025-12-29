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
   * Tests confirm route with status check.
   */
  public function testConfirmRoute(): void {
    // Confirm flow: getStatus() then confirm() if inactive.
    $this->setupMockHttpClient([
      // POST 1: status=inactive, then confirm succeeds.
      new Response(200, body: '{"subscriptionStatus": "inactive"}'),
      new Response(200, body: 'success'),
      // POST 2: status=active (already confirmed).
      new Response(200, body: '{"subscriptionStatus": "active"}'),
      // POST 3: status returns 404.
      new Response(404, body: 'not found'),
      // POST 4: status returns 500.
      new Response(500, body: 'fail'),
    ]);

    $this
      ->config('helfi_hakuvahti.settings')
      ->set('base_url', 'https://example.com')
      ->save();

    $this->setUpCurrentUser(permissions: ['access content']);

    $logger = $this->prophesize(LoggerInterface::class);
    $this->container->set('logger.channel.helfi_hakuvahti', $logger->reveal());

    $tests = [
      ['GET', 'Confirm saved search'],
      ['POST', 'Search saved successfully'],
      ['POST', 'Saved search already confirmed'],
      ['POST', 'Confirmation of saved search failed'],
      ['POST', 'Confirmation of saved search failed'],
    ];

    foreach ($tests as $test) {
      [$method, $message] = $test;

      $response = $this->makeRequest($method, 'helfi_hakuvahti.confirm', ['hash' => 'a', 'subscription' => 'b']);
      $this->assertEquals(200, $response->getStatusCode());
      $this->assertStringContainsString($message, $response->getContent() ?? '');
    }
  }

  /**
   * Tests renew and unsubscribe routes.
   *
   * @dataProvider dataProvider
   */
  public function testRenewAndUnsubscribeRoutes(string $route, array $tests): void {
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

    foreach ($tests as $test) {
      [$method, $message] = $test;

      $response = $this->makeRequest($method, $route, ['hash' => 'a', 'subscription' => 'b']);
      $this->assertEquals(200, $response->getStatusCode());
      $this->assertStringContainsString($message, $response->getContent() ?? '');
    }
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

  /**
   * Data provider for testRenewAndUnsubscribeRoutes.
   */
  private function dataProvider(): array {
    return [
      [
        'helfi_hakuvahti.renew',
        [
          ['GET', 'Renew saved search'],
          ['POST', 'Search renewed successfully'],
          ['POST', 'Renewal failed'],
          ['POST', 'Renewal failed'],
        ],
      ],
      [
        'helfi_hakuvahti.unsubscribe',
        [
          ['GET', 'Delete saved search'],
          ['POST', 'The saved search was successfully deleted.'],
          ['POST', 'Failed to delete saved search'],
          ['POST', 'Failed to delete saved search'],
        ],
      ],
    ];
  }

}
