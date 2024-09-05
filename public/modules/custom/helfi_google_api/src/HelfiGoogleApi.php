<?php

declare(strict_types=1);

namespace Drupal\helfi_google_api;

use Drupal\Core\Config\ConfigFactoryInterface;
use Google\Client as GoogleClient;
use Google\Service\Exception;
use Google\Service\Indexing;
use GuzzleHttp\Psr7\Request;

/**
 * A wrapper for Google indexing library.
 */
class HelfiGoogleApi {

  /**
   * Endpoint for sending update and delete requests.
   */
  const PUBLISH_ENDPOINT = 'https://indexing.googleapis.com/v3/urlNotifications:publish';

  /**
   * Endpoint for requesting url indexing status.
   */
  const METADATA_ENDPOINT = 'https://indexing.googleapis.com/v3/urlNotifications/metadata';

  /**
   * Api scopes.
   */
  const SCOPES = ['https://www.googleapis.com/auth/indexing'];

  /**
   * Request type for update-request (indexing).
   */
  const UPDATE = 'URL_UPDATED';

  /**
   * Request type for delete request (deindexing).
   */
  const DELETE = 'URL_DELETED';

  /**
   * Google indexing service.
   *
   * @var Google\Service\Indexing
   */
  private Indexing $indexingService;

  /**
   * The constructor.
   *
   * @param Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(
    private readonly ConfigFactoryInterface $configFactory,
  ) {
  }

  /**
   * Send indexing or deindexing request for urls.
   *
   * @param array $urls
   *   Array of urls to index or deindex.
   * @param bool $update
   *   TRUE to index the urls, FALSE for deindexing.
   *
   * @return array
   *   Array which consists of the amount of items sent and array of errors.
   */
  public function indexBatch(array $urls, bool $update): array {
    $this->initializeApi();
    $batch = $this->indexingService->createBatch();
    $operation = $update ? self::UPDATE : self::DELETE;

    foreach ($urls as $url) {
      $content = [
        'type' => $operation,
        'url' => $url,
      ];

      $request = new Request(
        method: 'POST',
        uri: self::PUBLISH_ENDPOINT,
        headers: ['Content-Type' => 'multipart/mixed'],
        body: json_encode($content)
      );

      $batch->add($request);
    }

    $responses = $batch->execute();

    $errors = [];
    foreach ($responses as $key => $response) {
      if ($response instanceof Exception) {
        $errors[] = "$key: {$response->getMessage()}";
      }
    }

    return [
      'count' => count($urls),
      'errors' => $errors,
    ];
  }

  /**
   * Request url indexing status.
   *
   * Returns the dates of last update and delete requests.
   *
   * @param string $url
   *   The url which indexing status you want to request.
   *
   * @return string
   *   The response as a string.
   */
  public function checkIndexingStatus($url): string {
    $this->initializeApi(FALSE);

    $base_url = self::METADATA_ENDPOINT;
    $queryParameter = '?url=' . urlencode($url);
    $the_url = $base_url . $queryParameter;

    $client = $this->indexingService->getClient()->authorize();
    $response = $client->get($the_url);

    return $response->getBody()->getContents();
  }

  /**
   * Set up the client.
   *
   * @param bool $batch
   *   Set the client on batch mode.
   *
   * @return void
   */
  private function initializeApi(bool $batch = TRUE): void {
    $config = $this->configFactory->get('helfi_google_api.settings');
    $key = $config->get('indexing_api_key') ?: '';

    if (!$key) {
      throw new \Exception('Api key not configured. Unable to proceed.');
    }

    $client = new GoogleClient();
    $client->setApplicationName('Helfi_Rekry');
    $client->setAuthConfig(json_decode($key, TRUE));

    $client->addScope(self::SCOPES);
    $client->setUseBatch($batch);

    $this->indexingService = new Indexing($client);
  }

}
