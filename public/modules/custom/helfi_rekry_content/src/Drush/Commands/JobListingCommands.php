<?php

declare(strict_types=1);

namespace Drupal\helfi_rekry_content\Drush\Commands;

use Drupal\helfi_rekry_content\Service\JobListingCleaner;
use Drush\Commands\AutowireTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for removing expired job listings.
 */
#[AsCommand(
  name: 'helfi-rekry-content:clean-expired-listings',
  description: 'Remove expired job listings.',
)]
final class JobListingCommands extends Command {

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
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $count = $this->jobListingCleaner->deleteExpired();
    $output->writeln(dt('@count job listings cleaned.', [
      '@count' => $count,
    ]));

    return Command::SUCCESS;
  }

}
