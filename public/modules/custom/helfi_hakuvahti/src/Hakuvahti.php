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
  public function unsubscribe(string $hash, string $subscription): void {
    $this->makeRequest('DELETE', "/subscription/delete/{$subscription}/{$hash}");
  }

  /**
   * Make hakuvahti request.
   *
   * @throws \Drupal\helfi_hakuvahti\HakuvahtiException
   */
  private function makeRequest(string $method, string $url, array $options = []): ResponseInterface {
    if (!$baseUrl = $this->configFactory->get('helfi_hakuvahti.settings')->get('base_url')) {
      throw new HakuvahtiException('Hakuvahti base url is not configured.');
    }

    // @todo hakuvahti has no use for Drupal tokens https://github.com/City-of-Helsinki/helfi-hakuvahti/blob/main/src/plugins/token.ts#L19.
    // Maybe this value could be kind of api-key, so
    // that only allowed services can talk to hakuvahti?
    $token = '123';

    try {
      return $this->client->request($method, "$baseUrl$url", NestedArray::mergeDeep([
        RequestOptions::HEADERS => [
          'Content-Type' => 'application/json',
          'token' => $token,
        ],
      ], $options));
    }
    catch (GuzzleException $exception) {
      throw new HakuvahtiException('Hakuvahti unsubscribe request failed: ' . $exception->getMessage(), $exception->getCode(), previous: $exception);
    }
  }

}
