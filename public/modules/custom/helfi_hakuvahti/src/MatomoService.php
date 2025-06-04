<?php

declare(strict_types=1);

namespace Drupal\helfi_hakuvahti;

use Drupal\Core\Queue\QueueFactory;
use Drupal\helfi_hakuvahti\Plugin\QueueWorker\MatomoWorker;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Service to handle custom dimension sending to Matomo.
 */
class MatomoService {
  public function __construct(
    #[Autowire(service: 'logger.channel.helfi_hakuvahti')]private readonly LoggerInterface $logger,
    private readonly QueueFactory $queueFactory,
  ) {
  }

  /**
   * Handle data which should be sent to.
   *
   * @param array $data
   *   The selected filters.
   */
  public function handleCustomDimensions(array $data): void {
    $queryString = $this->createCustomDimensionString($data);

    $queue = $this->queueFactory->get(MatomoWorker::$name)
      ->createItem($queryString);

    if (!$queue) {
      $this->logger->warning('Hakuvahti matomo-queue item not created.');
    }
  }

  /**
   * Create the string which is sent to Matomo.
   *
   * @param array $data
   *   Array of selected filters.
   *
   * @return string
   *   The query parameter string.
   */
  private function createCustomDimensionString(array $data): string {
    $dimensions = [];

    $i = 1;
    foreach ($data as $values) {
      foreach ($values as $value) {
        $dimensions["dimension$i"] = $value;
        $i++;
      }
    }

    $string = http_build_query($dimensions);

    return "&$string";
  }

}
