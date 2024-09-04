<?php

declare(strict_types=1);

namespace Drupal\helfi_google_api;

use Google\Client as GoogleClient;
use Google\Service\Indexing;
use GuzzleHttp\Psr7\Request;

class HelfiGoogleApi {

  const PUBLISH_ENDPOINT = 'https://indexing.googleapis.com/v3/urlNotifications:publish';

  const METADATA_ENDPOINT = 'https://indexing.googleapis.com/v3/urlNotifications/metadata';

  const BATCH_ENDPOINT = 'https://indexing.googleapis.com/batch';

  const SCOPES = ['https://www.googleapis.com/auth/indexing'];

  const UPDATE = 'URL_UPDATED';

  // todo Could be URL_REMOVED
  const DELETE = 'URL_DELETED';

  private Indexing $indexingService;

  public function __construct(
  ) {
  }

  /**
   * Send update request to indexing api, no more than 100 items at a time.
   *
   * @param array $jobs
   *   Array of job_listing nodes to be indexed or removed from index.
   *
   * @return void
   */
  public function indexBatch(array $urls, bool $update): void {

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

    die('LETS NOT SEND STUFF JUST YET');
    // $batch->execute();
  }

  public function checkIndexingStatus($url): string {
    $base_url = self::METADATA_ENDPOINT;
    $queryParameter = '?url=' . urlencode($url);
    $the_url = $base_url . $queryParameter;

    $this->initializeApi(FALSE);
    $client = $this->indexingService->getClient()->authorize();
    $response = $client->get($the_url);

    return $response->getBody()->getContents();
  }

  private function initializeApi($batch = TRUE) {
    // @todo Get correct key.
    $key = 'CHANGE THIS';
    $config = \Drupal::configFactory()->get('helfi_google_api.settings');
    $key = $config->get('indexing_api_key') ?: '';

    if (!$key) {
      throw new \Exception('Api key not configured. Unable to proceed.');
    }

    $client = new GoogleClient();
    $client->setApplicationName('Helfi_Rekry');
    // $client->setDeveloperKey($key);
    $client->setAuthConfig(json_decode($key, TRUE));

    $client->addScope(self::SCOPES);
    $client->setUseBatch($batch);

    $this->indexingService = new Indexing($client);
  }

}
