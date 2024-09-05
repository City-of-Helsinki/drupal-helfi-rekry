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
   *   Array: 'count': int, 'errors': array.
   */
  public function deindexItems(array $urls): array  {
    return $this->helfiGoogleApi->indexBatch($urls, FALSE);
  }

  /**
   * Handle entity indexing request
   *
   * @param JobListing $entity
   *   Entity to request deindexing for.
   *
   * @return array
   *   Array: 'count': int, 'errors': array
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

    try {
      $response = $this->deindexItems([$url_to_deindex]);
      $redirect->delete();
      return $response;
    } catch(GuzzleException $e) {
      $this->logger->error("Error while deindexing entity id {$entity->id()}: {$e->getMessage()}");
      throw new \Exception('Failed de-index redirect.');
    }
  }

  /**
   * Check url indexing status.
   *
   * @param string $url
   *   An url to check.
   *
   * @return string
   *   Status as a string.
   */
  public function checkItemIndexStatus(string $url): string {
    try {
      return $this->helfiGoogleApi->checkIndexingStatus($url);
    }
    catch (GuzzleException $e) {
      throw new \Exception($e->getMessage(), $e->getCode());
    }
  }

  /**
   * If entity seems to be indexed, send a status query.
   *
   * @param JobListing $entity
   * @return array
   */
  public function checkEntityIndexStatus(JobListing $entity): string {
    $language = $entity->language();

    $baseUrl = $this->generateFromRoute('<front>', [], ['absolute' => TRUE, 'language' => $language]);
    $job_alias = $this->aliasManager->getAliasByPath("/node/{$entity->id()}", $language->getId());

    $query = $this->entityTypeManager->getStorage('redirect')->getQuery();
    $redirectIds = $query->condition('redirect_redirect__uri', "internal:/node/{$entity->id()}")
      ->condition('status_code', 301)
      ->condition('language', $language->getId())
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
      // HAS NOT BEEN SENT AFAIK
    }

    $url_to_check = $baseUrl . $correct_redirect->getSourceUrl();
    try {
      $response = $this->helfiGoogleApi->checkIndexingStatus($url_to_check);
    }
    catch (\Exception $e) {
      // REQUEST FAILED

    }

    return $response;

  }

  /**
   * Does the entity have a temporary redirect.
   *
   * Temporary redirect is created for all entities before requesting indexing.
   * Once delete request is sent, the url cannot be indexed again using the api.
   * Hence we should not use the original url.
   *
   * @param JobListing $entity
   *   The entity to check.
   * @param string $langcode
   *   The language code.
   *
   * @return bool
   *   Has temporary redirect.
   */
  private function temporaryRedirectExists(JobListing $entity, string $langcode): bool {
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
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Create a redirect for the indexing request.
   *
   * Temporary redirect is created for all entities before requesting indexing.
   * Once delete request is sent, the url cannot be indexed again using the api.
   * Hence we should not use the original url.
   *
   * @param JobListing $entity
   *   The entity to index.
   * @param string $langcode
   *   The language code.
   *
   * @return string
   *   Absolute url that can be sent for indexing.
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

  /**
   * Get the temporary redirect url.
   *
   * @param JobListing $entity
   *   The entity to index.
   * @param string $langcode
   *   The language code.
   *
   * @return Redirect|null
   *   The redirect object.
   */
  public function getExistingTemporaryRedirect(JobListing $entity, string $langcode): Redirect|null {
    $job_alias = $this->getEntityAlias($entity, $langcode);

    $query = $this->entityTypeManager->getStorage('redirect')
      ->getQuery();

    $redirectIds = $query->condition('redirect_redirect__uri', "internal:/node/{$entity->id()}")
      ->condition('status_code', 301)
      ->condition('language', $langcode)
      ->accessCheck(FALSE)
      ->execute();
    $redirects = Redirect::loadMultiple($redirectIds);

    if (!$redirects) {
      return NULL;
    }

    foreach ($redirects as $redirect) {
      $source = $redirect->getSourceUrl();

      if (str_contains($source, "$job_alias-")) {
        return $redirect;
      }
    }
    return NULL;
  }

  /**
   * Get the alias for an entity.
   *
   * @param JobListing $entity
   *   The entity.
   * @param string $langcode
   *   The language code.
   *
   * @return string
   *   Alias for the entity.
   */
  private function getEntityAlias(JobListing $entity, string $langcode): string {
    return $this->aliasManager->getAliasByPath("/node/{$entity->id()}", $langcode);
  }

}
