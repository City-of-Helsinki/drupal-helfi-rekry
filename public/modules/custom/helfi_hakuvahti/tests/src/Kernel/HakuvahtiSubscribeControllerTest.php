<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_hakuvahti\Kernel;

use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\helfi_api_base\Traits\ApiTestTrait;
use Drupal\Tests\helfi_api_base\Traits\EnvironmentResolverTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\DependencyInjection\Loader\Configurator\Traits\PropertyTrait;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Tests for hakuvahti subscribe controller.
 */
class HakuvahtiSubscribeControllerTest extends KernelTestBase {

  use ApiTestTrait;
  use UserCreationTrait;
  use EnvironmentResolverTrait;
  use PropertyTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'system',
    'helfi_hakuvahti',
    'helfi_api_base',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['helfi_hakuvahti']);

    // Populate site_id in default config using entity storage.
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('hakuvahti_config');
    /** @var \Drupal\helfi_hakuvahti\Entity\HakuvahtiConfig|null $config */
    $config = $storage->load('default');
    if ($config) {
      $config->set('site_id', 'rekry');
      $config->save();
    }
  }

  /**
   * Tests subscribe endpoint with various scenarios.
   *
   * @group legacy
   */
  public function testSubscribeEndpoint(): void {
    $this->markTestSkipped('Mock HTTP client queue issue - needs investigation');
    $this->setUpCurrentUser(permissions: ['access content']);

    // Set base_url config.
    $this->config('helfi_hakuvahti.settings')
      ->set('base_url', 'https://example.com')
      ->save();

    // Test with API error.
    $client = $this->setupMockHttpClient([
      new RequestException('Test error', new Request('POST', 'test'), new Response(400)),
    ]);
    $this->container->set(ClientInterface::class, $client);

    $response = $this->makeRequest([
      'email' => 'valid@email.fi',
      'lang' => 'fi',
      'query' => '?query=123&parameters=4567',
      'elastic_query' => 'eyJxdWVyeSI6eyJib29sIjp7ImZpbHRlciI6W3sidGVybSI6eyJlbnRpdHlfdHlwZSI6Im5vZGUifX1dfX19',
      'search_description' => 'Test search',
    ]);
    $this->assertEquals(500, $response->getStatusCode());

    // Test with success.
    $client = $this->setupMockHttpClient([
      new Response(200),
    ]);
    $this->container->set(ClientInterface::class, $client);

    $response = $this->makeRequest([
      'email' => 'valid@email.fi',
      'lang' => 'fi',
      'query' => '?query=123&parameters=4567',
      'elastic_query' => 'eyJxdWVyeSI6eyJib29sIjp7ImZpbHRlciI6W3sidGVybSI6eyJlbnRpdHlfdHlwZSI6Im5vZGUifX1dfX19',
      'search_description' => 'Test search',
    ]);
    $this->assertEquals(200, $response->getStatusCode());
  }

  /**
   * Tests that non-existent config returns error.
   */
  public function testNonExistentConfigReturnsError(): void {
    $this->setUpCurrentUser(permissions: ['access content']);

    // Request with non-existent config parameter.
    $url = Url::fromRoute('helfi_hakuvahti.subscribe', [], ['query' => ['config' => 'nonexistent']]);
    $request = $this->getMockedRequest($url->toString(), 'POST', document: [
      'email' => 'valid@email.fi',
      'lang' => 'fi',
      'query' => '?query=123',
      'elastic_query' => 'eyJxdWVyeSI6eyJib29sIjp7ImZpbHRlciI6W3sidGVybSI6eyJlbnRpdHlfdHlwZSI6Im5vZGUifX1dfX19',
      'search_description' => 'Test',
    ]);

    $response = $this->processRequest($request);

    // Should return 400 error.
    $this->assertEquals(400, $response->getStatusCode());
    $data = json_decode($response->getContent(), TRUE);
    $this->assertFalse($data['success']);
    $this->assertStringContainsString('not found', $data['error']);
  }

  /**
   * Process a request.
   */
  private function makeRequest(array $body = []): SymfonyResponse {
    $url = Url::fromRoute('helfi_hakuvahti.subscribe');
    $request = $this->getMockedRequest($url->toString(), 'POST', document: $body);
    return $this->processRequest($request);
  }

}
