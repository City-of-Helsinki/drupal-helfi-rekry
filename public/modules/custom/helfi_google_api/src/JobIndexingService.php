<?php

declare(strict_types=1);

namespace Drupal\helfi_google_api;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\helfi_google_api\HelfiGoogleApi;

class JobIndexingService {

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly HelfiGoogleApi $helfiGoogleApi,
  ) {
  }

  /**
   * Update any jobs that were either published or unpublished today.
   *
   * @return void
   */
  public function indexUpdatedJobs(): void {
    $storage = $this->entityTypeManager
      ->getStorage('job_listing');
    $startOfToday = strtotime('today 00:05');
    $startOfYesterday = strtotime('yesterday 00:05');

    $this->helfiGoogleApi->indexJobs();
    $publishedJobIds = $storage->getQuery()
      ->condition('publish_on', $startOfToday, '>=')
      ->condition('status', 1)
      ->accessCheck(FALSE)
      ->execute();
    $publishedJobs = $storage->loadMultiple($publishedJobIds);

    $this->helfiGoogleApi->indexJobs($publishedJobs, 'update');

  }

}
