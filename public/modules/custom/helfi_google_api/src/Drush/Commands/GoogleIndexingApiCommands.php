<?php

declare(strict_types=1);

namespace Drupal\helfi_google_api\Drush\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\helfi_google_api\JobIndexingService;
use Drupal\helfi_google_api\Response;
use Drupal\helfi_rekry_content\Entity\JobListing;
use Drupal\node\Entity\Node;
use Drupal\path_alias\AliasManagerInterface;
use Drush\Attributes\Command;
use Drush\Commands\AutowireTrait;
use Drush\Commands\DrushCommands;

/**
 * A Drush command file.
 */
final class GoogleIndexingApiCommands extends DrushCommands {

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
  #[Command(name: 'helfi:google-single-entity-index')]
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

    return $this->handleResponse($response);
  }

  /**
   * Deindex single entity by id.
   *
   * @param int $entity_id
   *   The entity id.
   * @param string $langcode
   *   The entity langcode.
   *
   * @return int
   *   The exit code.
   */
  #[Command(name: 'helfi:google-single-entity-deindex')]
  public function deindexSingleItem(
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
      $response = $this->jobIndexingService->deindexEntity($entity);
    }
    catch (\Exception $e) {
      $this->io()->error($e->getMessage());
      return DrushCommands::EXIT_FAILURE;
    }

    return $this->handleResponse($response);
  }

  /**
   * Request url indexing status from Google api.
   *
   * @param string $url
   *   The url to check.
   *
   * @return int
   *   The exit code.
   */
  #[Command(name: 'helfi:google-url-index-status')]
  public function checkUrlIndexStatus(string $url): int {
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
  public function checkEntityIndexStatus(int $entity_id, $langcode = 'fi'): int {
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

  /**
   * Handle response.
   *
   * @param \Drupal\helfi_google_api\Response $response
   *   The response object.
   *
   * @return int
   *   Exit code.
   */
  private function handleResponse(Response $response): int {
    if ($response->getErrors()) {
      $this->io()->writeln('Request successful. Errors returned: ' . json_encode($response->getErrors()));
      return DrushCommands::EXIT_FAILURE_WITH_CLARITY;
    }

    if ($response->isDryRun()) {
      $urls = $response->getUrls();
      $this->io()->writeln('The api request would have sent following data: ' . json_encode($urls));
      return DrushCommands::EXIT_SUCCESS;
    }

    $this->io()->writeln('Url indexed succesfully.');
    return DrushCommands::EXIT_SUCCESS;
  }

}
