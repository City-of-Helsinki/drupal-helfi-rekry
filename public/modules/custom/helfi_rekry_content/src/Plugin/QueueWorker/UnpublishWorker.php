<?php

declare(strict_types=1);

namespace Drupal\helfi_rekry_content\Plugin\QueueWorker;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\Attribute\QueueWorker;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\helfi_rekry_content\Entity\JobListing;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Queue Worker for removing job listings not present in source.
 */
#[QueueWorker(
  id: 'job_listing_unpublish_worker',
  title: new TranslatableMarkup('Job listing unpublish worker'),
  cron: ['time' => 60],
)]
final class UnpublishWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
    protected ConfigFactoryInterface $configFactory,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) : self {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get(EntityTypeManagerInterface::class),
      $container->get('logger.channel.helfi_rekry_content'),
      $container->get(ConfigFactoryInterface::class),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data): void {
    // Sanity check that nid value exists.
    if (!$data['nid']) {
      return;
    }

    $nid = $data['nid'];
    $disableUnpublishing = (bool) $this->configFactory
      ->get('helfi_rekry_content.job_listings')
      ->get('disable_unpublishing');

    if (!$disableUnpublishing) {
      $nodeStorage = $this->entityTypeManager->getStorage('node');
      $node = $nodeStorage->load($nid);

      // Unpublish all translations.
      if ($node instanceof JobListing) {
        foreach ($node->getTranslationLanguages() as $language) {
          $langcode = $language->getId();

          // Unpublish the job listing node as it's still published, but it's
          // no longer available at the source.
          if (!$node->hasTranslation($langcode)) {
            continue;
          }

          $translation = $node->getTranslation($langcode);
          $translation->setUnpublished();

          // Also, clear the published on date so the translation
          // is not going to be re-published.
          if ($translation->hasField('publish_on') && !empty($translation->get('publish_on')->getValue())) {
            $translation->set('publish_on', NULL);
          }

          $translation->save();
        }
      }
    }

    $this->logger->notice(
      'Job listing with nid: @nid is missing from source data and has been unpublished.',
      [
        '@nid' => $nid,
      ]
    );
  }

}
