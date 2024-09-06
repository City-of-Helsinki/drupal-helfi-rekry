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
    if (!$this->helfiGoogleApi->hasAuthenticationKey()) {
      throw new \Exception('Api key not set.');
    }

    $langcode = $entity->language()->getId();

    $hasRedirect = $this->temporaryRedirectExists($entity, $langcode);
    if ($hasRedirect) {
      throw new \Exception('Already indexed.');
    }

    // Create temporary redirect for the entity.
    $indexing_url = $this->createTemporaryRedirectUrl($entity, $langcode);

    try {
      $result = $this->indexItems([$indexing_url]);
    }
    catch (GuzzleException $e) {
      $message = "Request failed with code {$e->getCode()}: {$e->getMessage()}";
      $this->logger->error($message);
      throw new \Exception($message);
    }

    if ($result['errors']) {
      $total = $result['count'];
      $error_count = count($result['errors']);
      $error_string = json_encode($result['errors']);
      $this->logger->error("Unable to index $error_count/$total items: $error_string");
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
  public function deindexItems(array $urls): array {
    return $this->helfiGoogleApi->indexBatch($urls, FALSE);
  }

  /**
   * Handle entity indexing request.
   *
   * @param \Drupal\helfi_rekry_content\Entity\JobListing $entity
   *   Entity to request deindexing for.
   *
   * @return array
   *   Array: 'count': int, 'errors': array
   */
  public function deindexEntity(JobListing $entity): array {
    if (!$this->helfiGoogleApi->hasAuthenticationKey()) {
      throw new \Exception('Api key not set.');
    }

    $language = $entity->language();
    $redirect = $this->getExistingTemporaryRedirect($entity, $language->getId());
    if (!$redirect) {
      $this->logger->error("Entity of id {$entity->id()} doesn't have the required temporary redirect.");
      throw new \Exception("Entity of id {$entity->id()} doesn't have the required temporary redirect.");
    }

    $base_url = $this->urlGenerator->generateFromRoute(
      '<front>',
      [],
      [
        'absolute' => TRUE,
        'language' => $language,
      ]
    );

    $url_to_deindex = $base_url . $redirect->getSourceUrl();

    try {
      $result = $this->deindexItems([$url_to_deindex]);
    }
    catch (GuzzleException $e) {
      $message = "Request failed with code {$e->getCode()}: {$e->getMessage()}";
      $this->logger->error($message);
      throw new \Exception($message);
    }

    if ($result['errors']) {
      $total = $result['count'];
      $error_count = count($result['errors']);
      $error_string = json_encode($result['errors']);
      $this->logger->error("Unable to index $error_count/$total items: $error_string");
    }

    return $result;
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
    return $this->helfiGoogleApi->checkIndexingStatus($url);
  }

  /**
   * If entity seems to be indexed, send a status query.
   *
   * @param \Drupal\helfi_rekry_content\Entity\JobListing $entity
   *   Entity to check.
   *
   * @return array
   */
  public function checkEntityIndexStatus(JobListing $entity): string {
    if (!$this->helfiGoogleApi->hasAuthenticationKey()) {
      throw new \Exception('Api key not set.');
    }

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
      throw new \Exception('Entity doesn\'t have temporary redirect.');
    }

    $url_to_check = $baseUrl . $correct_redirect->getSourceUrl();
    try {
      $response = $this->helfiGoogleApi->checkIndexingStatus($url_to_check);
    }
    catch (GuzzleException $e) {
      $this->logger->error("Request failed with code {$e->getCode()}: {$e->getMessage()}");
    }
    catch (\Exception $e) {
      $this->logger->error('Error while checking indexing status: ' . $e->getMessage());
    }

    return $response;
  }

  /**
   * Does the entity have a temporary redirect.
   *
   * Temporary redirect is created for all entities before requesting indexing.
   * Once delete-request is sent, the url cannot be indexed again using the api.
   * Hence we should not use the original url.
   *
   * @param Drupal\helfi_rekry_content\Entity\JobListing $entity
   *   The entity to check.
   * @param string $langcode
   *   The language code.
   *
   * @return bool
   *   Has temporary redirect.
   */
  public function temporaryRedirectExists(JobListing $entity, string $langcode): bool {
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
   * @param Drupal\helfi_rekry_content\Entity\JobListing $entity
   *   The entity to index.
   * @param string $langcode
   *   The language code.
   *
   * @return string
   *   Absolute url that can be sent for indexing.
   */
  public function createTemporaryRedirectUrl(JobListing $entity, string $langcode): string {
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
   * @param Drupal\helfi_rekry_content\Entity\JobListing $entity
   *   The entity to index.
   * @param string $langcode
   *   The language code.
   *
   * @return Drupal\redirect\Entity\Redirect|null
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
   * @param Drupal\helfi_rekry_content\Entity\JobListing $entity
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
