<?php

namespace Drupal\helfi_rekry_content\EventSubscriber;

use Drupal\Core\File\FileSystem;
use Drupal\media\Entity\Media;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class for subscribing to image import events.
 */
class ImageImportSubscriber implements EventSubscriberInterface {
  /**
   * URI for job listing images directory.
   */
  protected const IMAGES_DIR = 'public://job_listing_images/';

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\File\FileSystem $fileSystem
   *   The file system service.
   */
  public function __construct(private FileSystem $fileSystem) {
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      MigrateEvents::POST_ROW_SAVE => 'postRowSave',
      MigrateEvents::PRE_IMPORT => 'preImport',
    ];
  }

  /**
   * Create or update a media Entity of the file getting uploaded.
   *
   * @param \Drupal\migrate\Event\MigratePostRowSaveEvent $event
   *   The event object.
   */
  public function postRowSave(MigratePostRowSaveEvent $event): void {
    if (in_array($event->getMigration()->id(), $this->getImageMigrations())) {
      $row = $event->getRow();
      $title = $row->getSourceProperty('title');
      $fileId = reset($event->getDestinationIdValues());

      if ($mid = _helfi_rekry_content_get_media_image($fileId)) {
        $media = Media::load($mid);
        $media->setName($title)
          ->set('field_media_image', [
            'target_id' => $fileId,
            'alt' => $title,
          ])
          ->setPublished(TRUE)
          ->save();
      }
      else {
        $media = Media::create([
          'bundle' => 'job_listing_image',
          'uid' => \Drupal::currentUser()->id(),
          'name' => $title,
          'field_media_image' => [
            'target_id' => $fileId,
            'alt' => $title,
          ],
        ]);

        $media->setName(basename($row->getDestinationProperty('destination_filename')))
          ->setPublished(TRUE)
          ->save();
      }
    }
  }

  /**
   * Ensure that the directory for job listing images exists.
   *
   * @param \Drupal\migrate\Event\MigrateImportEvent $event
   *   The event object.
   */
  public function preImport(MigrateImportEvent $event): void {
    if (in_array($event->getMigration()->id(), $this->getImageMigrations()) && !file_exists(\Drupal::service('file_system')->realpath(self::IMAGES_DIR))) {
      $this->fileSystem->mkdir(self::IMAGES_DIR);
    }
  }

  /**
   * Return all possible events migrations.
   *
   * @return array
   *   The migration names.
   */
  protected function getImageMigrations(): array {
    return [
      'helfi_rekry_images',
      'helfi_rekry_images:all',
      'helfi_rekry_images:all_sv',
      'helfi_rekry_images:all_en',
    ];
  }

}
