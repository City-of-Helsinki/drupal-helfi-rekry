<?php

declare(strict_types=1);

namespace Drupal\helfi_google_api\Drush\Commands;

use Drupal\helfi_google_api\JobIndexingService;
use Drush\Attributes\Command;
use Drush\Commands\AutowireTrait;
use Drush\Commands\DrushCommands;

/**
 * A Drush command file.
 */
final class HelfiApiCommands extends DrushCommands {

  use AutowireTrait;

  public function __construct(
    private readonly JobIndexingService $jobIndexingService,
  ) {
    parent::__construct();
  }

  #[Command(name: 'helfi:index-google')]
  public function process() : int {
    $this->jobIndexingService->indexUpdatedJobs();
    return DrushCommands::EXIT_SUCCESS;
  }

}
