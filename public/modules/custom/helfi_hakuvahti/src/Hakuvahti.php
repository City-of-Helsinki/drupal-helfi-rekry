<?php

declare(strict_types=1);

namespace Drupal\helfi_hakuvahti;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;

/**
 * Hakuvahti API client.
 */
final readonly class Hakuvahti implements HakuvahtiInterface {

  public function __construct(
    private ClientInterface $client,
    private ConfigFactoryInterface $configFactory,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function subscribe(HakuvahtiRequest $request): void {
    $this->makeRequest('POST', "/subscription", [
      RequestOptions::JSON => $request->getServiceRequestData(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function confirm(string $subscriptionHash, string $subscriptionId): void {
    $this->makeRequest('GET', "/subscription/confirm/{$subscriptionId}/{$subscriptionHash}");
  }

  /**
   * {@inheritdoc}
   */
  public function renew(string $subscriptionHash, string $subscriptionId): void {
    $this->makeRequest('GET', "/subscription/renew/{$subscriptionId}/{$subscriptionHash}");
  }

  /**
   * {@inheritdoc}
   */
  public function unsubscribe(string $subscriptionHash, string $subscriptionId): void {
    $this->makeRequest('DELETE', "/subscription/delete/{$subscriptionId}/{$subscriptionHash}");
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus(string $subscriptionHash, string $subscriptionId): ?string {
    try {
      $response = $this->makeRequest('GET', "/subscription/status/{$subscriptionId}/{$subscriptionHash}");
    }
    catch (HakuvahtiException $exception) {
      // 404 means subscription not found.
      if ($exception->getCode() === 404) {
        return NULL;
      }
      throw $exception;
    }

    $data = json_decode($response->getBody()->getContents(), TRUE);
    return $data['subscriptionStatus'] ?? NULL;
  }

  /**
   * Make hakuvahti request.
   *
   * @throws \Drupal\helfi_hakuvahti\HakuvahtiException
   */
  private function makeRequest(string $method, string $url, array $options = []): ResponseInterface {
    $settings = $this->configFactory->get('helfi_hakuvahti.settings');
    if (!$baseUrl = $settings->get('base_url')) {
      throw new HakuvahtiException('Hakuvahti base url is not configured.');
    }

    $apiKey = $settings->get('api_key');

    try {
      return $this->client->request($method, "$baseUrl$url", NestedArray::mergeDeep([
        RequestOptions::HEADERS => [
          'Authorization' => "api-key $apiKey",
          // @todo remove this when we have fully migrated to new Hakuvahti.
          'token' => '123',
        ],
        RequestOptions::TIMEOUT => 5,
      ], $options));
    }
    catch (GuzzleException $exception) {
      throw new HakuvahtiException('Hakuvahti unsubscribe request failed: ' . $exception->getMessage(), $exception->getCode(), previous: $exception);
    }
  }

}
