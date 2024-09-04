<?php

declare(strict_types=1);

namespace Drupal\helfi_google_api\Drush\Commands;

use Drupal\helfi_google_api\JobIndexingService;
use Drupal\helfi_rekry_content\Entity\JobListing;
use Drupal\node\Entity\Node;
use Drupal\redirect\Entity\Redirect;
use Drush\Attributes\Command;
use Drush\Commands\AutowireTrait;
use Drush\Commands\DrushCommands;

/**
 * A Drush command file.
 */
final class HelfiApiCommands extends DrushCommands {

  use AutowireTrait;

  public function __construct(
    private readonly JobIndexingService $jobIndexingService,
  ) {
    parent::__construct();
  }

  #[Command(name: 'helfi:single-index-google')]
  public function indexSingleItem(
    int $entity_id,
    string $langcode = 'fi',
  ) : int {
    $job = Node::load($entity_id);
    if (!$job instanceof JobListing) {
      $this->io()->writeln('Entity not found or not instance of JobListing');
      return DrushCommands::EXIT_FAILURE;
    }

    $job_alias = \Drupal::service('path_alias.manager')->getAliasByPath("/node/{$job->id()}", $langcode);

    // Check if entity has already been indexed.
    $redirectIds = \Drupal::entityQuery('redirect')
      ->condition('redirect_redirect__uri', "internal:/node/{$job->id()}")
      ->condition('status_code', 301)
      ->condition('language', $langcode)
      ->accessCheck(FALSE)
      ->execute();

    $redirects = Redirect::loadMultiple($redirectIds);

    foreach ($redirects as $redirect) {
      $source = $redirect->getSourceUrl();

      if (str_contains($source, "$job_alias-")) {
        $this->io()->writeln('Entity has temporary redirect which indicates
          that indexing has already been requested.');
        return DrushCommands::EXIT_FAILURE;
      }
    }

    $now = strtotime('now');
    $temp_alias = "$job_alias-$now";
    $indexing_url = "{$job->toUrl()->setAbsolute()->toString()}-$now";

    $redirect = Redirect::create([
      'redirect_source' => ltrim($temp_alias, '/'),
      'redirect_redirect' => "internal:/node/{$job->id()}",
      'language' => $langcode,
      'status_code' => 301,
    ]);

    try {
      $redirect->save();
    }
    catch (\Exception $e) {
      $this->io()->writeln('Failed to create temporary redirect for the entity.');
      return DrushCommands::EXIT_FAILURE;
    }

    $this->jobIndexingService->indexItem($indexing_url);
    return DrushCommands::EXIT_SUCCESS;
  }

  #[Command(name: 'helfi:google-index-status-check')]
  public function checkItemIndexStatus(int $entity_id, $langcode = 'fi') {
    $job = Node::load($entity_id);
    if (!$job instanceof JobListing) {
      $this->io()->writeln('Entity not found or not instance of JobListing');
      return DrushCommands::EXIT_FAILURE;
    }

    $language = \Drupal::languageManager()->getLanguage($langcode);
    $baseUrl = \Drupal::urlGenerator()->generateFromRoute('<front>', [], ['absolute' => TRUE, 'language' => $language]);
    $job_alias = \Drupal::service('path_alias.manager')->getAliasByPath("/node/{$job->id()}", $langcode);

    // Check if entity has already been indexed.
    $redirectIds = \Drupal::entityQuery('redirect')
      ->condition('redirect_redirect__uri', "internal:/node/{$job->id()}")
      ->condition('status_code', 301)
      ->condition('language', $langcode)
      ->accessCheck(FALSE)
      ->execute();

    // Get the indexed redirect.
    $redirects = Redirect::loadMultiple($redirectIds);
    foreach ($redirects as $redirect) {
      $source = $redirect->getSourceUrl();

      if (str_contains($source, "$job_alias-")) {
        $correct_redirect = $redirect;
        break;
      }
    }

    if (!$correct_redirect) {
      $this->io()->writeln('Redirect request has not been sent.');
      return DrushCommands::EXIT_FAILURE;
    }

    $url_to_check = $baseUrl . $redirect->getSourceUrl();
    try {
      $response = $this->jobIndexingService->checkItemIndexStatus($url_to_check);
    }
    catch (\Exception $e) {
      $this->io()->writeln('Something went wrong: ' . $e->getMessage());
      return DrushCommands::EXIT_FAILURE;
    }

    $this->io()->writeln($response);
    return DrushCommands::EXIT_SUCCESS;
  }

}
