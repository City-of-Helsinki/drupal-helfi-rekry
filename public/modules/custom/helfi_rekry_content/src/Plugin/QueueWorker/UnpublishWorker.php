<?php

namespace Drupal\helfi_rekry_content\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\node\NodeInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Queue Worker for removing job listings not present in source.
 *
 * @QueueWorker(
 *   id = "job_listing_unpublish_worker",
 *   title = @Translation("Job listing unpublish worker"),
 *   cron = {"time" = 60}
 * )
 */
class UnpublishWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a new UnpublishWorker object.
   *
   * @param array $configuration
   *   Configuration array.
   * @param mixed $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger channel.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * Creates instance of the plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container.
   * @param array $configuration
   *   Configuration array.
   * @param mixed $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   *
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
    $configuration,
    $plugin_id,
    $plugin_definition,
    $container->get('entity_type.manager'),
    $container->get('logger.channel.helfi_rekry_content'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    // Sanity check that nid value exists.
    if (!$data['nid']) {
      return;
    }

    $nid = $data['nid'];
    $nodeStorage = $this->entityTypeManager->getStorage('node');
    $node = $nodeStorage->load($nid);

    // Unpublish all translations.
    if ($node instanceof NodeInterface && $node->getType() == 'job_listing') {
      foreach (['fi', 'sv', 'en'] as $langcode) {
        // Unpublish the job listing node as it's still published, but it's
        // no longer available at the source.
        if (!$node->hasTranslation($langcode)) {
          continue;
        }

        $translation = $node->getTranslation($langcode);
        if ($translation->isPublished()) {
          $translation->setUnpublished();
          if ($translation->hasField('publish_on') && !empty($translation->get('publish_on')->getValue())) {
            // Also clear the publish on date to make sure the translation is
            // not going to be re-published.
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
