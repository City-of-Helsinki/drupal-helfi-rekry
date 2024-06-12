<?php

declare(strict_types=1);

namespace Drupal\helfi_rekry_content\Drush\Commands;

use Drupal\helfi_rekry_content\Service\JobListingCleaner;
use Drush\Attributes as CLI;
use Drush\Commands\AutowireTrait;
use Drush\Commands\DrushCommands;

/**
 * A Drush command file.
 */
final class JobListingCommands extends DrushCommands {

  use AutowireTrait;

  /**
   * Constructs a JobListingCommands object.
   */
  public function __construct(
    private readonly JobListingCleaner $jobListingCleaner,
  ) {
    parent::__construct();
  }

  /**
   * Command for removing expired job listings.
   */
  #[CLI\Command(name: 'helfi-rekry-content:clean-expired-listings')]
  #[CLI\Usage(name: 'helfi-rekry-content:clean-expired-listings', description: 'Remove expired job listings')]
  public function cleanExpired(): void {
    $count = $this->jobListingCleaner->deleteExpired();
    $this->logger()->success(dt('@count job listings cleaned.', [
      '@count' => $count,
    ]));
  }

}
