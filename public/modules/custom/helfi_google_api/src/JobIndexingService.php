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
    private readonly GoogleApi $googleApi,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly AliasManagerInterface $aliasManager,
    private readonly UrlGeneratorInterface $urlGenerator,
    private readonly LoggerInterface $logger,
  ) {
  }

  /**
   * Send urls to Google for indexing or deindexing.
   *
   * @param array $urls
   *   Urls to add or remove from index.
   * @param bool $update
   *   Send update or delete request (indexing or deindexing).
   *
   * @return \Drupal\helfi_google_api\Response
   *   The response object.
   */
  public function handleIndexingRequest(array $urls, bool $update): Response {
    try {
      $response = $this->googleApi->indexBatch($urls, $update);
      $this->handleDebugMessage($response);
      return $response;
    }
    catch (GuzzleException $e) {
      $message = "Request failed with code {$e->getCode()}: {$e->getMessage()}";
      $this->logger->error($message);
      throw new \Exception($message);
    }
  }

  /**
   * Send indexing request to google.
   *
   * @param \Drupal\helfi_rekry_content\Entity\JobListing $entity
   *   Entity which indexing should be requested.
   *
   * @return \Drupal\helfi_google_api\Response
   *   The response object.
   */
  public function indexEntity(JobListing $entity): Response {
    $langcode = $entity->language()->getId();

    $hasRedirect = $this->hasTemporaryRedirect($entity, $langcode);
    if ($hasRedirect) {
      throw new \Exception('Already indexed.');
    }

    // Create temporary redirect for the entity.
    $redirectArray = $this->createTemporaryRedirectUrl($entity, $langcode);
    $indexing_url = $redirectArray['indexing_url'];
    $redirect = $redirectArray['redirect'];

    try {
      $result = $this->handleIndexingRequest([$indexing_url], TRUE);
    }
    catch (\Exception $e) {
      // If the request fails, remove the redirect.
      $redirect->delete();
      throw $e;
    }

    if ($result->getErrors()) {
      $total = count($result->getUrls());
      $errorCount = count($result->getErrors());
      $errorsString = json_encode($result->getErrors());
      $this->logger->error(("Unable to index $errorCount/$total items: $errorsString"));
    }

    return $result;
  }

  /**
   * Handle entity deindexing request.
   *
   * @param \Drupal\helfi_rekry_content\Entity\JobListing $entity
   *   Entity to request deindexing for.
   *
   * @return \Drupal\helfi_google_api\Response
   *   The response object.
   */
  public function deindexEntity(JobListing $entity): Response {
    $language = $entity->language();
    $redirect = $this->getExistingTemporaryRedirect($entity, $language->getId());
    if (!$redirect) {
      $message = "Entity of id {$entity->id()} doesn't have the required temporary redirect.";
      $this->logger->error($message);
      throw new \Exception($message);
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
      $result = $this->handleIndexingRequest([$url_to_deindex], FALSE);
    }
    catch (\Exception $e) {
      throw $e;
    }

    // No need to delete redirects on debug run.
    if (!$result->isDryRun()) {
      $redirect->delete();
    }

    if ($result->getErrors()) {
      $total = count($result->getUrls());
      $errorCount = count($result->getErrors());
      $errorsString = json_encode($result->getErrors());
      $this->logger->error(("Unable to index $errorCount/$total items: $errorsString"));
    }

    return $result;
  }

  /**
   * Check url indexing status.
   *
   * Status check request uses the api quota.
   *
   * @param string $url
   *   An url to check.
   *
   * @return string
   *   Status as a string.
   */
  public function checkItemIndexStatus(string $url): string {
    return $this->googleApi->checkIndexingStatus($url);
  }

  /**
   * If entity seems to be indexed, send a status query.
   *
   * Status check request uses the api quota.
   *
   * @param \Drupal\helfi_rekry_content\Entity\JobListing $entity
   *   Entity to check.
   *
   * @return string
   *   The url index status as a string.
   */
  public function checkEntityIndexStatus(JobListing $entity): string {
    $language = $entity->language();

    $baseUrl = $this->urlGenerator->generateFromRoute('<front>', [], ['absolute' => TRUE, 'language' => $language]);
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

    if (!isset($correct_redirect)) {
      throw new \Exception('Entity doesn\'t have temporary redirect.');
    }

    $url_to_check = $baseUrl . $correct_redirect->getSourceUrl();
    try {
      return $this->googleApi->checkIndexingStatus($url_to_check);
    }
    catch (GuzzleException $e) {
      $this->logger->error("Request failed with code {$e->getCode()}: {$e->getMessage()}");
      throw new \Exception($e->getMessage());
    }
    catch (\Exception $e) {
      $this->logger->error('Error while checking indexing status: ' . $e->getMessage());
      throw $e;
    }

  }

  /**
   * Does the entity have a temporary redirect.
   *
   * Temporary redirect is created for all entities before requesting indexing.
   * Once delete-request is sent, the url cannot be indexed again using the api.
   * Hence we should not use the original url.
   *
   * @param \Drupal\helfi_rekry_content\Entity\JobListing $entity
   *   The entity to check.
   * @param string $langcode
   *   The language code.
   *
   * @return bool
   *   Has temporary redirect.
   */
  public function hasTemporaryRedirect(JobListing $entity, string $langcode): bool {
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
   * @param \Drupal\helfi_rekry_content\Entity\JobListing $entity
   *   The entity to index.
   * @param string $langcode
   *   The language code.
   *
   * @return array
   *   Indexing_url as the absolute url and the redirect object.
   */
  public function createTemporaryRedirectUrl(JobListing $entity, string $langcode): array {
    $alias = $this->getEntityAlias($entity, $langcode);
    $now = strtotime('now');
    $temp_alias = "$alias-$now";
    $indexing_url = "{$entity->toUrl()->setAbsolute()->toString()}-$now";

    $redirect = Redirect::create([
      'redirect_source' => ltrim($temp_alias, '/'),
      'redirect_redirect' => "internal:/node/{$entity->id()}",
      'language' => $langcode,
      'status_code' => 301,
    ]);

    // Only save the redirect if module set up properly.
    if (!$this->googleApi->isDryRun()) {
      $redirect->save();
    }

    return ['indexing_url' => $indexing_url, 'redirect' => $redirect];
  }

  /**
   * Get the temporary redirect url.
   *
   * @param \Drupal\helfi_rekry_content\Entity\JobListing $entity
   *   The entity to index.
   * @param string $langcode
   *   The language code.
   *
   * @return \Drupal\redirect\Entity\Redirect|null
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

    // For debugging purposes, debugging won't save the redirect.
    if (!$redirects && $this->googleApi->isDryRun()) {
      return $this->createTemporaryRedirectUrl($entity, $langcode)['redirect'];
    }

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
   * @param \Drupal\helfi_rekry_content\Entity\JobListing $entity
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

  /**
   * Send debug message if in debug mode.
   *
   * @param Response $response
   *   The response.
   */
  private function handleDebugMessage(Response $response) {
    if ($response->isDryRun()) {
      $this->logger->debug('Request would have sent following urls to api: '. json_encode($response->getUrls()));
    }
  }

}
