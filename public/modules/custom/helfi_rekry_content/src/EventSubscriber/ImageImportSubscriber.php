<?php

declare(strict_types = 1);

namespace Drupal\helfi_rekry_content\EventSubscriber;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Session\AccountProxyInterface;
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
   * Constructs a new instance.
   *
   * @param \Drupal\Core\File\FileSystem $fileSystem
   *   The file system service.
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   The settings service.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   */
  public function __construct(
    private FileSystem $fileSystem,
    private ConfigFactory $config,
    private AccountProxyInterface $currentUser
  ) {
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
    // Return early if not image migration.
    if (!in_array($event->getMigration()->id(), $this->getImageMigrations())) {
      return;
    }

    $row = $event->getRow();
    $title = $row->getSourceProperty('title');
    $destinationIdValues = $event->getDestinationIdValues();
    $fileId = reset($destinationIdValues);

    if ($mid = _helfi_rekry_content_get_media_image($fileId)) {
      $media = Media::load($mid);
      $media->setName($title)
        ->set('field_media_image', [
          'target_id' => $fileId,
          'alt' => $title,
        ])
        ->setPublished()
        ->save();
    }
    else {
      $media = Media::create([
        'bundle' => 'job_listing_image',
        'uid' => $this->currentUser->id(),
        'name' => $title,
        'field_media_image' => [
          'target_id' => $fileId,
          'alt' => $title,
        ],
      ]);

      $media->setName(basename($row->getDestinationProperty('destination_filename')))
        ->setPublished()
        ->save();
    }
  }

  /**
   * Ensure that the directory for job listing images exists.
   *
   * @param \Drupal\migrate\Event\MigrateImportEvent $event
   *   The event object.
   */
  public function preImport(MigrateImportEvent $event): void {
    if (in_array($event->getMigration()->id(), $this->getImageMigrations()) && !file_exists($this->fileSystem->realpath($this->getImagesDir()))) {
      $this->fileSystem->mkdir($this->getImagesDir());
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

  /**
   * Return the uri for images folder.
   *
   * @return string
   *   The uri.
   */
  protected function getImagesDir(): string {
    $defaultScheme = $this->config->get('system.file')->get('default_scheme');

    return $defaultScheme . '://job_listing_images/';
  }

}
