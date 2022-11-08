<?php

declare(strict_types=1);

namespace Drupal\helfi_rekry_content\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class for subscribing to "organization" taxonomy term imports.
 */
class OrganizationImportSubscriber implements EventSubscriberInterface {

  /**
   * Constructs a new OrganizationImportSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      MigrateEvents::POST_ROW_SAVE => 'postRowSave',
    ];
  }

  /**
   * Check and and add translations for imported terms.
   */
  public function postRowSave(MigratePostRowSaveEvent $event) : void {
    // Skip other than organization translation migrations.
    $migration_id = $event->getMigration()->id();

    if (!in_array($migration_id, $this->getOrganizationTranslationMigrations())) {
      return;
    }

    $tids = $event->getDestinationIdValues();

    $term = $this->entityTypeManager
      ->getStorage('taxonomy_term')
      ->load(reset($tids));

    // Get the external ID.
    $external_id = $term->get('field_external_id')->getString();

    // Get the Finnish term with the external ID.
    $finnish_term_query = $this->entityTypeManager
      ->getStorage('taxonomy_term')
      ->loadByProperties([
        'field_external_id' => $external_id,
        'langcode' => 'fi',
      ]);

    $finnish_term = reset($finnish_term_query);

    // Get the migration langcode.
    $migration_langcode = $this->getMigrationLangcode($migration_id);

    // If a translation already exists, remove it.
    if ($finnish_term->hasTranslation($migration_langcode)) {
      $finnish_term->removeTranslation($migration_langcode);
    }

    // Add translation to the term & save.
    $finnish_term->addTranslation($migration_langcode, [
      'name' => $term->label(),
    ])->save();
  }

  /**
   * Return all possible organization migrations.
   *
   * @return array
   *   The migration names.
   */
  protected function getOrganizationTranslationMigrations(): array {
    return [
      'helfi_rekry_organizations:all_sv',
      'helfi_rekry_organizations:all_en',
      'helfi_rekry_organizations:changed_sv',
      'helfi_rekry_organizations:changed_en',
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
    else {
      return 'fi';
    }
  }

}
