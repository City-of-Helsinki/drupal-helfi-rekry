<?php

declare(strict_types=1);

namespace Drupal\helfi_rekry_content\Helbit;

use Drupal\Core\Utility\Error;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Utils;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Helbit API client.
 */
final readonly class HelbitClient {

  /**
   * Constructs a HelbitClient object.
   */
  public function __construct(
    #[Autowire(service: 'logger.channel.helfi_rekry_content')]
    private LoggerInterface $logger,
    private ClientInterface $client,
    private Settings $config,
  ) {
  }

  /**
   * Get job listings.
   *
   * @param string $language
   *   Result langcode.
   *
   * @return array
   *   Job listing data.
   */
  public function getJobListings(string $language, array $query = []): array {
    try {
      $response = $this->makeRequest('/open-jobs', [
        'query' => $query + [
          'lang' => $this->getHelbitLangcode($language),
        ],
      ]);

      if ($response['status'] === 'OK') {
        return $response['jobAdvertisements'] ?? [];
      }

      $this->logger->error('Failed retrieving data from Helbit. Request failed with code: @status_code', [
        '@status_code' => $response['status'] ?? '',
      ]);
    }
    catch (RequestException | GuzzleException $e) {
      Error::logException($this->logger, $e);
    }

    return [];
  }

  /**
   * Make request to Helbit API.
   *
   * @param string $endpoint
   *   Api endpoint.
   * @param array $options
   *   Http client options.
   *
   * @return array
   *   Parsed response.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  private function makeRequest(string $endpoint, array $options): array {
    $options['query']['client'] = $this->config->clientId;

    $response = $this->client->request('GET', "https://helbit.fi/portal-api/recruitment/v2.3$endpoint", $options);

    return Utils::jsonDecode($response->getBody()->getContents(), TRUE);
  }

  /**
   * Convert langcode to Helbit format.
   *
   * @param string $langcode
   *   Langcode.
   *
   * @return string
   *   Helbit langcode.
   */
  private function getHelbitLangcode(string $langcode): string {
    return match ($langcode) {
      'sv' => 'sv_SE',
      'en' => 'en_US',
      'fi' => 'fi_FI',
    };
  }

}
