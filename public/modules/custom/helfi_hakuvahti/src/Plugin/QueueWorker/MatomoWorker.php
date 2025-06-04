<?php

declare(strict_types=1);

namespace Drupal\helfi_hakuvahti\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handle Matomo-requests
 */
class MatomoWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Name for the queue.
   *
   * @var string
   */
  public static string $name = 'hakuvahti_matomo_worker';

  /**
   * {@inheritDoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly LoggerInterface $logger,
    private readonly ClientInterface $client,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.channel.helfi_hakuvahti'),
      $container->get('http_client'),
    );
  }

  /**
   * {@inheritDoc}
   */
  public function processItem($data):void {
    // Data should be querystring that we can send to matomo directly.
  }

}
