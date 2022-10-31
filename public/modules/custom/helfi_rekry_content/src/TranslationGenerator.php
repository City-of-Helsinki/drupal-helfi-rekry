<?php

namespace Drupal\helfi_rekry_content;

use Drupal\node\Entity\Node;

/**
 * Class for creating missing language versions.
 */
class TranslationGenerator {

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
      $listing->addTranslation($langcode, $listing->toArray());
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
