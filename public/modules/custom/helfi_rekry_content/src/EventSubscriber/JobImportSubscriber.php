<?php

declare(strict_types = 1);

namespace Drupal\helfi_rekry_content\EventSubscriber;

use Drupal\helfi_rekry_content\Plugin\QueueWorker\TranslationsQueue;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class for subscribing to job import events.
 */
class JobImportSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      MigrateEvents::POST_ROW_SAVE => 'postRowSave',
    ];
  }

  /**
   * Add created nodes to queue that creates translations for them.
   */
  public function postRowSave(MigratePostRowSaveEvent $event): void {
    $migrationId = $event->getMigration()->id();
    // Return early if not jos listing migration.
    if (!in_array($migrationId, $this->getJobMigrations())) {
      return;
    }

    $langcode = $this->getMigrationLangcode($migrationId);
    $queue = \Drupal::service('queue')->get(TranslationsQueue::QUEUE_ID);
    $nids = $event->getDestinationIdValues();

    foreach ($nids as $nid) {
      $item = new \stdClass();
      $item->nid = $nid;
      $item->langcode = $langcode;
      $queue->createItem($item);
    }
  }

  /**
   * Return all possible job listing migrations.
   *
   * @return array
   *   The migration names.
   */
  protected function getJobMigrations(): array {
    return [
      'helfi_rekry_jobs',
      'helfi_rekry_jobs:all',
      'helfi_rekry_jobs:all_sv',
      'helfi_rekry_jobs:all_en',
      'helfi_rekry_jobs:changed',
      'helfi_rekry_jobs:changed_sv',
      'helfi_rekry_jobs:changed_en',
    ];
  }

  /**
   * Get langcode from language specific migration ID.
   *
   * @param string $migrationId
   *   The language specific migration ID.
   *
   * @return string
   *   The langcode.
   */
  protected function getMigrationLangcode(string $migrationId): string {
    if (str_contains($migrationId, '_sv')) {
      return 'sv';
    }
    elseif (str_contains($migrationId, '_en')) {
      return 'en';
    }
    return 'fi';
  }

}
