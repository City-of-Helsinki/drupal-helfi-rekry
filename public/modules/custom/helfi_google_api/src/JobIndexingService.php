<?php

declare(strict_types=1);

namespace Drupal\helfi_google_api;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\redirect\Entity\Redirect;
use GuzzleHttp\Exception\GuzzleException;

class JobIndexingService {

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly HelfiGoogleApi $helfiGoogleApi,
    private readonly AliasManagerInterface $aliasManager,
  ) {
  }

  /**
   * Send single item to google for indexing.
   *
   * @param string $url
   *   Url to index.
   *
   * @return void
   */
  public function indexItem(string $url) {
    $this->helfiGoogleApi->indexBatch([$url], TRUE);
  }

  public function deindexItem(string $url) {
    $this->helfiGoogleApi->indexBatch([$url], FALSE);
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

  // TODO get urls function should not create redirects
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
