<?php

declare(strict_types=1);

namespace Drupal\helfi_google_api;

use Google\Client as GoogleClient;
use Google\Service\Indexing\UrlNotification;
use Google\Service\Indexing;

class HelfiGoogleApi {

  const END_POINT = 'https://indexing.googleapis.com/v3/urlNotifications:publish';

  const SCOPES = ['https://www.googleapis.com/auth/indexing'];

  public function __construct(
    private readonly GoogleClient $apiClient
  ) {
    // $indexing = new Indexing();
    // $apiClient->setApplicationName('Helfi_Rekry');
    //$indexing = new UrlNotification();
    // $apiClient->setDeveloperKey();
    // $apiClient->setAuthConfig();
    // $this->apiClient->setUseBatch(TRUE);
    // $batch = $this->apiClient->execute();

    // $apiClient->setScopes(self::SCOPES);
  }

  public function indexJobs(array $jobs, string $type): void {
  }

}
