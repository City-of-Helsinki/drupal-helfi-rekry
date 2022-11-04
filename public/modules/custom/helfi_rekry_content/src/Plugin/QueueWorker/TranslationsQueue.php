<?php

declare(strict_types = 1);

namespace Drupal\helfi_rekry_content\Plugin\Queueworker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Queue worker for kob listing translations.
 *
 * @QueueWorker(
 *   id = "helfi_rekry_job_translations",
 *   title = @Translation("Job listing translations"),
 *   cron = {"time" = 60}
 * )
 */
final class TranslationsQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Queue id.
   */
  public const QUEUE_ID = 'helfi_rekry_job_translations';

  /**
   * Create a static instance.
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
      $plugin_definition
    );
  }

  /**
   * Creates translations for jobs as needed.
   *
   * @param mixed $data
   *   Node data to work with.
   */
  public function processItem($data): void {

    if (!$data->nid) {
      return;
    }

    $this->createTranslations($data->nid);
  }

  /**
   * Create missing language versions.
   *
   * @param string $id
   *   Job listing node id.
   */
  public function createTranslations(string $id): void {
    $listing = Node::load($id);

    if (!$listing) {
      return;
    }

    $missingVersions = $this->getMissingVersions($listing);

    if (count($missingVersions) < 1) {
      return;
    }

    foreach ($missingVersions as $langcode) {
      $originalLangcode = $listing->get('langcode')->value;
      $listing->addTranslation($langcode, array_merge($listing->toArray(), [
        'field_copied' => [
          ['value' => TRUE],
        ],
        'field_original_language' => [
          ['value' => $originalLangcode],
        ],
      ]));

      $listing->save();
    }
  }

  /**
   * Checks which translations need to be created.
   *
   * @param \Drupal\node\Entity\Node $node
   *   Node entity to check for.
   */
  private function getMissingVersions(Node $node) {
    $langcodes = ['fi', 'sv', 'en'];
    $missing = [];

    foreach ($langcodes as $langcode) {
      if (!$node->hasTranslation($langcode)) {
        $missing[] = $langcode;
      }
    }

    return $missing;
  }

}
