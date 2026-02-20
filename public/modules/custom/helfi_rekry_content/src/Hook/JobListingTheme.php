<?php

declare(strict_types=1);

namespace Drupal\helfi_rekry_content\Hook;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\helfi_hakuvahti\DrupalSettings;

/**
 * Theme hooks for job listings.
 */
final readonly class JobListingTheme {

  public function __construct(
    private LanguageManagerInterface $languageManager,
    private EntityTypeManagerInterface $entityTypeManager,
    private DrupalSettings $drupalSettings,
  ) {
  }

  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Hook('preprocess_paragraph__job_search')]
  public function preprocessParagraph(array &$variables): void {
    /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
    $paragraph = $variables['paragraph'];
    $paragraph_type = $paragraph->getType();
    $langcode = $this->languageManager
      ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)
      ->getId();

    if ($paragraph_type == 'job_search') {
      if ($search_result_page_nid = $paragraph->get('field_job_search_result_page')->getString()) {

        $entity = $this->entityTypeManager
          ->getStorage('node')
          ->load($search_result_page_nid);

        if ($entity->hasTranslation($langcode)) {
          $entity = $entity->getTranslation($langcode);
        }

        $url = $entity->toUrl()->toString();
        $variables['#attached']['drupalSettings']['helfi_rekry_job_search']['results_page_path'] = $url;
      }
    }

    // Apply hakuvahti settings.
    $this->drupalSettings->applyTo($variables);
  }

}
