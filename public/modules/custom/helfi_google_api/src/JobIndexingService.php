<?php

declare(strict_types=1);

namespace Drupal\helfi_google_api;

use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\helfi_rekry_content\Entity\JobListing;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\redirect\Entity\Redirect;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

/**
 * Send indexing requests to Google indexing api.
 */
class JobIndexingService {
  use AutowireTrait;

  public function __construct(
    private readonly HelfiGoogleApi $helfiGoogleApi,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly AliasManagerInterface $aliasManager,
    private readonly UrlGeneratorInterface $urlGenerator,
    private readonly LoggerInterface $logger,
  ) {
  }

  /**
   * Send urls to google for indexing.
   *
   * @param array $urls
   *   Array of urls to index.
   *
   * @return array
   *   Array of containing total count indexed and errors.
   */
  public function indexItems(array $urls): array {
    return $this->helfiGoogleApi->indexBatch($urls, TRUE);
  }

  /**
   * Send indexing request to google.
   *
   * @param Drupal\helfi_rekry_content\Entity\JobListing $entity
   *   Entity which indexing should be requested.
   *
   * @return array
   *   Array of containing total count indexed and errors.
   */
  public function indexEntity(JobListing $entity): array {
    $langcode = $entity->language()->getId();
    $results = [];

    // If the actual url is deindexed, it can't be reindexed again.
    // If entity has a temporary redirect, it most likely has already been indexed already.
    $hasRedirect = $this->temporaryRedirectExists($entity, $langcode);
    if ($hasRedirect) {
      throw new \Exception('Already indexed.');
    }

    // Create temporary redirect for the entity.
    $indexing_url = $this->createTemporaryRedirectUrl($entity, $langcode);

    $result = $this->indexItems([$indexing_url]);

    if ($result['errors']) {
      // Some of the urls failed
      // log
    }

    return $result;
  }


  /**
   * Send urls to Google for deindexing.
   *
   * @param array $urls
   *   Urls to remove from index.
   *
   * @return array
   *
   */
  public function deindexItems(array $urls): array  {
    return $this->helfiGoogleApi->indexBatch($urls, FALSE);
  }

  /**
   * Handle entity indexing request
   *
   * @param JobListing $entity
   * @return array
   * @throws \Exception
   */
  public function deindexEntity(JobListing $entity): array {
    $language = $entity->language();
    $redirect = $this->getExistingTemporaryRedirect($entity, $language->getId());
    if (!$redirect) {
      // Log, the item seems not to be indexed.
      // Return.
      throw new \Exception();
    }

    $base_url = $this->urlGenerator->generateFromRoute(
      '<front>',
      [],
      ['absolute' => TRUE,'language' => $language]
    );

    $url_to_deindex = $base_url . $redirect->getSourceUrl();

    return $this->deindexItems([$url_to_deindex]);
  }

  private function temporaryRedirectExists(JobListing $entity, string $langcode): bool {
    $job_alias = $this->getEntityAlias($entity, $langcode);
    // Check if entity has already been indexed.

    $query = $this->entityTypeManager->getStorage('redirect')
      ->getQuery();

    $redirectIds = $query->condition('redirect_redirect__uri', "internal:/node/{$entity->id()}")
      ->condition('status_code', 301)
      ->condition('language', $langcode)
      ->accessCheck(FALSE)
      ->execute();
    $redirects = Redirect::loadMultiple($redirectIds);

    foreach ($redirects as $redirect) {
      $source = $redirect->getSourceUrl();

      if (str_contains($source, "$job_alias-")) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Create a redirect for the indexing request.
   *
   * @param JobListing $entity
   *   The entity to index.
   * @param string $langcode
   *
   *
   * @return string
   *   Absolute url that can be send for indexing.
   */
  private function createTemporaryRedirectUrl(JobListing $entity, string $langcode): string {
    $alias = $this->getEntityAlias($entity, $langcode);
    $now = strtotime('now');
    $temp_alias = "$alias-$now";
    $indexing_url = "{$entity->toUrl()->setAbsolute()->toString()}-$now";

    Redirect::create([
      'redirect_source' => ltrim($temp_alias, '/'),
      'redirect_redirect' => "internal:/node/{$entity->id()}",
      'language' => $langcode,
      'status_code' => 301,
    ])->save();

    return $indexing_url;
  }

  private function getExistingTemporaryRedirect(JobListing $entity, string $langcode): Redirect|null {
    $job_alias = $this->getEntityAlias($entity, $langcode);

    $query = $this->entityTypeManager->getStorage('redirect')
      ->getQuery();

    $redirectIds = $query->condition('redirect_redirect__uri', "internal:/node/{$entity->id()}")
      ->condition('status_code', 301)
      ->condition('language', $langcode)
      ->accessCheck(FALSE)
      ->execute();
    $redirects = Redirect::loadMultiple($redirectIds);

    foreach ($redirects as $redirect) {
      $source = $redirect->getSourceUrl();

      if (str_contains($source, "$job_alias-")) {
        return $redirect;
      }
    }
    return NULL;
  }

  private function getEntityAlias(JobListing $entity, string $langcode): string {
    return $this->aliasManager->getAliasByPath("/node/{$entity->id()}", $langcode);
  }

  public function checkItemIndexStatus(string $url): string {
    try {
      return $this->helfiGoogleApi->checkIndexingStatus($url);
    }
    catch (GuzzleException $e) {
      throw new \Exception($e->getMessage(), $e->getCode());
    }
  }

  /**
   * Update any jobs that were published today.
   *
   * @return void
   */
  public function indexJobs(): void {
    $storage = $this->entityTypeManager
      ->getStorage('node');
    $startOfToday = strtotime('today 00:00:00');
    $endOfToday = strtotime('today 23:59:59');

    // Etsi nodet joiden publish_on on tänään
    // TAI ei oo tänään mutta on luotu tänään

    $jobIds = $storage->getQuery()
      // ->condition('publish_on', $startOfToday, '>=')
      // ->condition('publish_on', $endOfToday, '<=')
      ->condition('status', 1)
      ->condition('type', 'job_listing')
      ->latestRevision()
      ->accessCheck(FALSE)
      ->execute();

    if (!$jobIds) {
      return;
    }

    $jobs = $storage->loadMultiple($jobIds);
    $urls = $this->prepareUrls($jobs);

    $this->helfiGoogleApi->indexJobsBatch($urls);
  }

  public function deleteIndexedJobs() {

    $storage = $this->entityTypeManager
      ->getStorage('node');
    $startOfToday = strtotime('today 00:00:00');
    $endOfToday = strtotime('today 23:59:59');

    // Etsi nodet joiden unpublish on on tänään
    $jobIds = $storage->getQuery()
      ->condition('unpublish_on', $startOfToday, '>=')
      ->condition('unpublish_on', $endOfToday, '<=')
      ->condition('status', 0)
      ->condition('type', 'job_listing')
      ->latestRevision()
      ->accessCheck(FALSE)
      ->execute();

    if (!$jobIds) {
      return;
    }

    $jobs = $storage->loadMultiple($jobIds);

    // hae redirectit
    $redirect = $this->getRedirects($jobs);

    // kaiva se urli sieltä
    // lähetä googlelle
    // poista redirect

    // $this->helfiGoogleApi->indexJobsBatch($urls);
    // $urls = $this->getUrls($jobs);

    // Redirect::load()

  }

  private function prepareUrls(array $jobs, array $langcodes = ['fi', 'en', 'sv']): array {
    $result = [];

    $now = strtotime('now');

    foreach ($jobs as $job) {

      foreach($langcodes as $langcode) {
        if ($job->hasTranslation($langcode)) {
          $job = $job->getTranslation($langcode);
          $alias = $this->aliasManager->getAliasByPath("/node/{$job->id()}", $langcode);
          $temp_alias = "{$alias}-{$now}";

          $redirect = Redirect::create([
            'redirect_source' => ltrim($temp_alias, '/'),
            'redirect_redirect' => "internal:/node/{$job->id()}",
            'language' => $langcode,
            'status_code' => 301,
            'title' => $job->getTitle(),
          ]);

          // $redirect->setRedirect( "/node/{$job->id()}", [], ['redirect_title' => $job->getTitle()]);
          $save_status = $redirect->save();

          if ($save_status === SAVED_NEW) {
            $index_url = "{$job->toUrl()->setAbsolute(TRUE)->toString()}-{$now}";
            $result[] = $index_url;
          }

        }
      }
    }

    return $result;
  }

  /**
   * Delete a temporary redirect.
   *
   * @param array $jobs
   *   Array of nodes.
   *
   * @return array
   */
  private function deleteRedirect(array $jobs): array {
    $result = [];
    $langcodes = ['fi', 'en', 'sv'];

    foreach ($jobs as $job) {
      foreach($langcodes as $langcode) {
        if ($job->hasTranslation($langcode)) {
          $job = $job->getTranslation($langcode);

          $redirectIds = \Drupal::entityQuery('redirect')
            ->condition('redirect_redirect__uri', "internal:/node/{$job->id()}")
            ->condition('status_code', 301)
            ->condition('language', $langcode)
            ->execute();

          $redirects = Redirect::loadMultiple($redirectIds);
          /** @var Redirect $redirect */
          foreach ($redirects as $redirect) {
            $alias = $this->aliasManager->getAliasByPath("/node/{$job->id()}", $langcode);
            $source = $redirect->getSourceUrl();
            if (!str_contains($source, "$alias-")) {
              continue;
            }

            $result[] = $source;
            try {
              $redirect->delete();
            }
            catch(\Exception $e) {
              // Add logging.
            }

          }
        }
      }

      return $result;
    }

    return $result;
  }

}
