<?php

declare(strict_types=1);

namespace Drupal\helfi_google_api;

use Drupal\Core\Config\ConfigFactoryInterface;
use Google\Client as GoogleClient;
use Google\Service\Indexing;

/**
 * Create and set up Google service and client.
 */
class GoogleServiceFactory {

  /**
   * Api scopes.
   */
  const SCOPES = ['https://www.googleapis.com/auth/indexing'];

  /**
   * Google service factory method.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   *
   * @return \Google\Service\Indexing
   *   Google indexing service.
   */
  public function create(ConfigFactoryInterface $configFactory): Indexing {
    $config = $configFactory->get('helfi_google_api.settings');
    $key = $config->get('indexing_api_key') ?: '';

    $client = new GoogleClient();
    $client->setApplicationName('Helfi_Rekry');
    $client->addScope(self::SCOPES);
    $client->setUseBatch(TRUE);

    if ($key) {
      $client->setAuthConfig(json_decode($key, TRUE));
      $client->authorize();
    }

    return new Indexing($client);
  }

}
