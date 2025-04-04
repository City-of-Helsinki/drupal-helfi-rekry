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
class HelbitClient {

  /**
   * Constructs a HelbitClient object.
   */
  public function __construct(
    #[Autowire(service: 'logger.channel.helfi_rekry_content')]
    private readonly LoggerInterface $logger,
    private readonly ClientInterface $client,
    private readonly Settings $config,
  ) {
  }

  /**
   * Get job listings.
   *
   * @param string $language
   *   Result langcode.
   * @param array $query
   *   Additional query parameters.
   *
   * @return array
   *   Job listing data.
   */
  public function getJobListings(string $language, array $query = []): array {
    $jobListings = [];

    foreach ($this->config->clients as $environment) {
      try {
        $response = $this->makeRequest($environment, '/open-jobs', [
          'query' => $query + [
            'lang' => $this->getHelbitLangcode($language),
          ],
        ]);

        if ($response['status'] === 'OK') {
          // Insert current environment URL into each application.
          if (isset($response['jobAdvertisements'])) {
            foreach ($response['jobAdvertisements'] as &$job) {
              $job['baseUrl'] = $environment->baseUrl;
            }
          }

          $jobListings = array_merge($jobListings, $response['jobAdvertisements'] ?? []);
        }
        else {
          $this->logger->error('Failed retrieving data from Helbit. Request failed with code: @status_code', [
            '@status_code' => $response['status'] ?? '',
          ]);
        }
      }
      catch (RequestException | GuzzleException $e) {
        Error::logException($this->logger, $e);
      }
    }

    return $jobListings;
  }

  /**
   * Make request to Helbit API.
   *
   * @param \Drupal\helfi_rekry_content\Helbit\HelbitEnvironment $environment
   *   Helbit environment.
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
  private function makeRequest(HelbitEnvironment $environment, string $endpoint, array $options): array {
    $options['query']['client'] = $environment->clientId;
    $baseUrl = $environment->baseUrl;

    $response = $this->client->request('GET', "$baseUrl/portal-api/recruitment/v2.3$endpoint", $options);

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
