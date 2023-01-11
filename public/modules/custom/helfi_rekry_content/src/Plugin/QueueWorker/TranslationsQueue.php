<?php

declare(strict_types = 1);

namespace Drupal\helfi_rekry_content\Plugin\Queueworker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Queue worker for job listing translations.
 *
 * @QueueWorker(
 *   id = "helfi_rekry_job_translations",
 *   title = @Translation("Job listing translations"),
 *   cron = {"time" = 900}
 * )
 */
final class TranslationsQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Queue id.
   */
  public const QUEUE_ID = 'helfi_rekry_job_translations';

  /**
   * All possible langcode values.
   *
   * @var protectedarray
   */
  protected array $langcodes = [
    'en' => 'en',
    'fi' => 'fi',
    'sv' => 'sv',
  ];

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

    $this->handleTranslations($data->nid);
  }

  /**
   * Create / update forced translations.
   *
   * @param string $id
   *   Job listing node id.
   */
  public function handleTranslations(string $id): void {
    $listing = Node::load($id);

    if (!$listing) {
      return;
    }

    $originalLangcode = $listing->get('langcode')->value;

    foreach ($this->langcodes as $langcode) {
      if ($langcode === $originalLangcode) {
        continue;
      }

      $existing = NULL;
      $translated = $listing->hasTranslation($langcode);

      if ($translated) {
        $existing = $listing->getTranslation($langcode);
      }

      if ($existing && $this->shouldNotUpdate($existing)) {
        return;
      }

      $translation = $existing ? $existing : $listing->addTranslation($langcode, array_merge($listing->toArray(), [
        'field_copied' => [
          ['value' => TRUE],
        ],
        'field_original_language' => [
          ['value' => $originalLangcode],
        ],
      ]));

      // Publish the translation if needed.
      if ($listing->isPublished()) {
        $translation->setPublished(TRUE);
      }

      $listing->save();
    }
  }

  /**
   * Handle updating existing translations.
   */
  private function shouldNotUpdate(Node $translation) {
    // Don't update listings that haver their own translation in Helbit.
    if ($translation->get('field_copied')->getValue() === FALSE) {
      return;
    }
  }

}
