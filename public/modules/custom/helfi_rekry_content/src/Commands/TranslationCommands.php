<?php

namespace Drupal\helfi_rekry_content\Commands;

use Drupal\helfi_rekry_content\TranslationGenerator;
use Drush\Commands\DrushCommands;

/**
 * Defines translation command.
 *
 * @package Drupal\helfi_rekry_content\Commands
 */
class TranslationCommands extends DrushCommands {

  /**
   * Drush command for generating missing translations.
   *
   * @command helfi_rekry_content:generate-translations
   *
   * @usage helfi_rekry_content:generate-translations
   *   Generate missing translations for job listings.
   *
   * @aliases hrc:gt
   */
  public function generateTranslations(): void {
    $this->output()->writeln('Generating translations for job listings...');
    $ids = $this->getIds();

    if (!count($ids)) {
      $this->output()->writeln('No ids found to work with.');
      return;
    }

    $translationGenerator = new TranslationGenerator();

    foreach ($ids as $id) {
      $translationGenerator->createTranslations($id);
    }

    $this->output()->writeln('Finished generating translations.');
  }

  /**
   * Return a list of job listing ID:s that need to be checked.
   */
  private function getIds(): array {
    return [2949];
  }

}
