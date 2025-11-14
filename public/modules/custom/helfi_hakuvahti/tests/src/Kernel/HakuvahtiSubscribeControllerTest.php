<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_hakuvahti\Kernel;

use Drupal\Core\Entity\EntityTypeManagerInterface;
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
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $client = $this->setupMockHttpClient([
      new RequestException('Test error', new Request('POST', 'test'), new Response(400)),
      new Response(200),
    ]);

    $this->container->set(ClientInterface::class, $client);

    // Populate site_id in default config using entity storage.
    $this->container->get(EntityTypeManagerInterface::class)
      ->getStorage('hakuvahti_config')
      ->create([
        'id' => 'default',
        'label' => 'Foobar',
        'site_id' => 'rekry',
      ])
      ->save();
  }

  /**
   * Tests handleConfirmFormSubmission.
   */
  public function testHandleConfirmFormSubmission(): void {
    // Subscribe without permissions.
    $response = $this->makeRequest([]);
    $this->assertEquals(403, $response->getStatusCode());

    $this->setUpCurrentUser(permissions: ['access content']);

    // Subscribe with bad request.
    $response = $this->makeRequest([]);
    $this->assertEquals(400, $response->getStatusCode());

    // Missing config.
    $response = $this->makeRequest([
      'email' => 'valid@email.fi',
      'lang' => 'fi',
      'query' => '?query=123&parameters=4567',
      'elastic_query' => 'eyJxdWVyeSI6eyJib29sIjp7ImZpbHRlciI6W3sidGVybSI6eyJlbnRpdHlfdHlwZSI6Im5vZGUifX1dfX19',
      'search_description' => 'This, is the query filters string, separated, by comma',
    ]);
    $this->assertEquals(500, $response->getStatusCode());

    $this->config('helfi_hakuvahti.settings')
      ->set('base_url', 'https://example.com')
      ->save();

    // Subscribe with api error.
    $response = $this->makeRequest([
      'email' => 'valid@email.fi',
      'lang' => 'fi',
      'query' => '?query=123&parameters=4567',
      'elastic_query' => 'eyJxdWVyeSI6eyJib29sIjp7ImZpbHRlciI6W3sidGVybSI6eyJlbnRpdHlfdHlwZSI6Im5vZGUifX1dfX19',
      'search_description' => 'This, is the query filters string, separated, by comma',
    ]);
    $this->assertEquals(500, $response->getStatusCode());

    // Success.
    $response = $this->makeRequest([
      'email' => 'valid@email.fi',
      'lang' => 'fi',
      'query' => '?query=123&parameters=4567',
      'elastic_query' => 'eyJxdWVyeSI6eyJib29sIjp7ImZpbHRlciI6W3sidGVybSI6eyJlbnRpdHlfdHlwZSI6Im5vZGUifX1dfX19',
      'search_description' => 'This, is the query filters string, separated, by comma',
    ]);
    $this->assertEquals(200, $response->getStatusCode());
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
