<?php

declare(strict_types=1);

namespace Drupal\helfi_google_api;

use Drupal\Core\Config\ConfigFactoryInterface;
use Google\Service\Exception;
use Google\Service\Indexing;
use GuzzleHttp\Psr7\Request;

/**
 * A wrapper for Google indexing library.
 */
class GoogleApi {

  /**
   * Endpoint for sending update and delete requests.
   */
  const PUBLISH_ENDPOINT = 'https://indexing.googleapis.com/v3/urlNotifications:publish';

  /**
   * Endpoint for requesting url indexing status.
   */
  const METADATA_ENDPOINT = 'https://indexing.googleapis.com/v3/urlNotifications/metadata';

  /**
   * Request type for update-request (indexing).
   */
  const UPDATE = 'URL_UPDATED';

  /**
   * Request type for delete request (deindexing).
   */
  const DELETE = 'URL_DELETED';

  /**
   * The constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Google\Service\Indexing $indexingService
   *   The Google indexing service.
   */
  public function __construct(
    private readonly ConfigFactoryInterface $configFactory,
    private readonly Indexing $indexingService,
  ) {
  }

  /**
   * Correct environment and key is set and enabled is true.
   *
   * @return bool
   *   The api is set up
   */
  public function isDryRun(): bool {
    $config = $this->configFactory->get('helfi_google_api.settings');
    $key = $config->get('indexing_api_key') ?: '';
    $dryRun = $config->get('dry_run') ?: TRUE;

    return !$key || $dryRun;
  }

  /**
   * Send indexing or deindexing request for urls.
   *
   * @param array $urls
   *   Array of urls to index or deindex.
   * @param bool $update
   *   TRUE to index the urls, FALSE for deindexing.
   *
   * @return Response
   *   Object which holds the handled urls and request errors.
   */
  public function indexBatch(array $urls, bool $update): Response {
    if ($this->isDryRun()) {
      return new Response($urls, dryRun: TRUE);
    }

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

    return new Response($urls, $errors);
  }

  /**
   * Request url indexing status.
   *
   * Returns the dates of last update and delete requests.
   * For debugging purposes only, since it spends the quota.
   *
   * @param string $url
   *   The url which indexing status you want to request.
   *
   * @return string
   *   The response as a string.
   */
  public function checkIndexingStatus(string $url): string {
    $client = $this->indexingService->getClient();
    $client->setUseBatch(FALSE);

    if ($this->isDryRun()) {
      return "Dry running index status query with url: $url";
    }

    $client = $client->authorize();

    $baseUrl = self::METADATA_ENDPOINT;
    $query_parameter = '?url=' . urlencode($url);
    $theUrl = $baseUrl . $query_parameter;

    $result = $client->request('GET', $theUrl);
    return $result->getBody()->getContents();
  }

}
