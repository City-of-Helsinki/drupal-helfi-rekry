<?php

declare(strict_types=1);

namespace Drupal\helfi_google_api\Drush\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\helfi_google_api\JobIndexingService;
use Drupal\helfi_rekry_content\Entity\JobListing;
use Drupal\node\Entity\Node;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\redirect\Entity\Redirect;
use Drush\Attributes\Command;
use Drush\Commands\AutowireTrait;
use Drush\Commands\DrushCommands;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * A Drush command file.
 */
final class HelfiApiCommands extends DrushCommands {

  use AutowireTrait;

  public function __construct(
    private readonly JobIndexingService $jobIndexingService,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly LanguageManagerInterface $languageManager,
    private readonly AliasManagerInterface $aliasManager,
    private readonly UrlGeneratorInterface $urlGenerator,
  ) {
    parent::__construct();
  }

  /**
   * Index single job listing.
   *
   * @param int $entity_id
   *   The entity id.
   * @param string $langcode
   *   The language code.
   *
   * @return int
   *   The exit code.
   */
  #[Command(name: 'helfi:single-index-google')]
  public function indexSingleItem(
    int $entity_id,
    string $langcode = 'fi',
  ) : int {
    $entity = Node::load($entity_id);

    if (!$entity instanceof JobListing) {
      $this->io()->writeln('Entity not found or not instance of JobListing');
      return DrushCommands::EXIT_FAILURE;
    }

    if (!$entity->hasTranslation($langcode)) {
      $this->io()->writeln('Translation does not exist.');
      return DrushCommands::EXIT_FAILURE;
    }
    $entity = $entity->getTranslation($langcode);

    try {
      $response = $this->jobIndexingService->indexEntity($entity);
    }
    catch (\Exception $e) {
      $this->io()->error($e->getMessage());
      return DrushCommands::EXIT_FAILURE;
    }

    if ($response['errors']) {
      $this->io()->writeln('Request successful. Errors returned: ' . json_encode($response['errors']));
      return DrushCommands::EXIT_FAILURE_WITH_CLARITY;
    }

    $this->io()->writeln('Url indexed succesfully.');
    return DrushCommands::EXIT_SUCCESS;
  }

  /**
   * Request url indexing status from Google api.
   *
   * @param int $url
   *   The url to check.
   *
   * @return int
   *   The exit code.
   */
  #[Command(name: 'helfi:google-url-index-status')]
  public function checkUrlIndexStatus(string $url) {
    try {
      $response = $this->jobIndexingService->checkItemIndexStatus($url);
    }
    catch (\Exception $e) {
      $this->io()->writeln($e->getMessage());
      return DrushCommands::EXIT_FAILURE;
    }

    $this->io()->writeln($response);
    return DrushCommands::EXIT_SUCCESS;
  }

  /**
   * Check entity indexing status.
   *
   * @param int $entity_id
   *   The entity id.
   * @param string $langcode
   *   The language code.
   *
   * @return int
   *   The exit code.
   */
  #[Command(name: 'helfi:google-entity-index-status')]
  public function checkEntityIndexStatus(int $entity_id, $langcode = 'fi') {
    $entity = Node::load($entity_id);

    if (!$entity instanceof JobListing) {
      $this->io()->writeln('Entity not found or not instance of JobListing');
      return DrushCommands::EXIT_FAILURE;
    }

    if (!$entity->hasTranslation($langcode)) {
      $this->io()->writeln('Translation does not exist.');
      return DrushCommands::EXIT_FAILURE;
    }
    $entity = $entity->getTranslation($langcode);

    try {
      $response = $this->jobIndexingService->checkEntityIndexStatus($entity);
    }
    catch (\Exception $e) {
      $this->io()->writeln($e->getMessage());
      return DrushCommands::EXIT_FAILURE;
    }

    $this->io()->writeln($response);
    return DrushCommands::EXIT_SUCCESS;
  }

}
