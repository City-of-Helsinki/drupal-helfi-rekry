<?php

declare(strict_types = 1);

namespace Drupal\helfi_rekry_content\Drush\Commands;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\helfi_rekry_content\Service\JobListingCleaner;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Drush commandfile.
 */
final class JobListingCommands extends DrushCommands implements ContainerInjectionInterface {

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
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get(JobListingCleaner::class),
    );
  }

  /**
   * Command description here.
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