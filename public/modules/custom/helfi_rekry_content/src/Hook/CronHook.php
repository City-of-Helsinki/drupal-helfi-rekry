<?php

declare(strict_types=1);

namespace Drupal\helfi_rekry_content\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\State\StateInterface;
use Drupal\helfi_rekry_content\Service\HakuvahtiTracker;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Cron hook implementations for helfi_rekry_content.
 */
class CronHook {

  public function __construct(
    private readonly HakuvahtiTracker $hakuvahtiTracker,
    private readonly StateInterface $state,
    #[Autowire(service: 'datetime.time')]
    private readonly \Drupal\Component\Datetime\TimeInterface $time,
    #[Autowire(service: 'logger.channel.helfi_rekry_content')]
    private readonly LoggerInterface $logger,
  ) {}

  /**
   * Implements hook_cron().
   *
   * Deletes hakuvahti filter entries older than 3 years.
   * Runs once per day using state-based throttling.
   */
  #[Hook('cron')]
  public function cron(): void {
    $last_run = $this->state->get('helfi_rekry_content.hakuvahti_cleanup_last_run', 0);
    $request_time = $this->time->getRequestTime();

    // 24 hours in seconds.
    $one_day = 86400;

    // Only run once per day.
    if ($request_time - $last_run < $one_day) {
      return;
    }

    try {
      $this->hakuvahtiTracker->deleteOldEntries();
      $this->state->set('helfi_rekry_content.hakuvahti_cleanup_last_run', $request_time);
    }
    catch (\Exception $e) {
      $this->logger->error('Hakuvahti cleanup failed: @message', ['@message' => $e->getMessage()]);
    }
  }

}
