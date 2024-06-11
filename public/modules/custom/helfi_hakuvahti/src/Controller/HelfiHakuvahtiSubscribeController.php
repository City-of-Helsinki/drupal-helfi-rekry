<?php

declare(strict_types=1);

namespace Drupal\helfi_hakuvahti\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Creates new subscription.
 */
final class HelfiHakuvahtiSubscribeController extends ControllerBase {

  use StringTranslationTrait;

  /**
   * Constructor for the HelfiHakuvahtiSubscribeController class.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $_entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    protected ContainerInterface $container,
    protected RequestStack $requestStack,
    protected EntityTypeManagerInterface $_entityTypeManager,
  ) {}

  /**
   * Retrieves taxonomy labels by field_external_id values in a given language.
   *
   * @param array $external_ids
   *   An array of external ID values to match.
   * @param string $language
   *   The language code for the desired translation.
   *
   * @return array
   *   An array of taxonomy term labels in the specified language.
   */
  private function getLabelsByExternalId(array $external_ids, $language) {
    $labels = [];
    $terms = $this->_entityTypeManager->getStorage('taxonomy_term')->loadByProperties(['field_external_id' => $external_ids]);
    foreach ($terms as $term) {
      $translated_term = $term->hasTranslation($language) ? $term->getTranslation($language) : $term;
      $labels[] = $translated_term->label();
    }
    return $labels;
  }

  /**
   * Retrieves taxonomy labels by their taxonomy term IDs in a given language.
   *
   * @param array $term_ids
   *   An array of taxonomy term IDs to load.
   * @param string $language
   *   The language code for the desired translation.
   *
   * @return array
   *   An array of taxonomy term labels in the specified language.
   */
  private function getLabelsByTermIds(array $term_ids, $language) {
    $labels = [];
    $terms = $this->_entityTypeManager->getStorage('taxonomy_term')->loadMultiple($term_ids);
    foreach ($terms as $term) {
      $translated_term = $term->hasTranslation($language) ? $term->getTranslation($language) : $term;
      $labels[] = $translated_term->label();
    }

    return $labels;
  }

  /**
   * Function to get translated string in a given language.
   *
   * phpcs:ignore is used to mute error about string literals as there
   *   is no other way to do this translation.
   *
   * @param string $string
   *   The string to be translated.
   * @param string $language
   *   The language code for the desired translation.
   *
   * @return string
   *   The translated string.
   */
  private function translateString($string, $language) {
    $translatedString = $this->t($string, [], ['langcode' => $language]); // @phpcs:ignore
    return $translatedString;
  }

  /**
   * Function to extract specific query parameters from a URL string.
   *
   * @param string $url
   *   The URL string to parse.
   * @param string $parameter
   *   The query parameter to extract values for.
   *
   * @return array
   *   An array of values for the specified query parameter.
   */
  private function extractQueryParameters($url, $parameter) {
    $parsed_url = parse_url($url);
    $query = $parsed_url['query'] ?? '';
    $query_parameters = [];
    $pairs = explode('&', $query);

    foreach ($pairs as $pair) {
      if (empty($pair)) {
        continue;
      }

      // Split the key and value, using + [null, null] to ensure both are set.
      [$key, $value] = explode('=', $pair, 2) + [NULL, NULL];
      if ($key === NULL) {
        continue;
      }

      $key = urldecode($key);
      $value = urldecode($value);

      // If the parameter is the one we're looking for, add it to the array.
      if ($key === $parameter) {
        $query_parameters[] = $value;
      }
    }

    return $query_parameters;
  }

  /**
   * Retrieves search description taxonomies from the provided object.
   *
   * @param mixed $obj
   *   The object containing elastic query data.
   *
   * @return string
   *   The concatenated search description taxonomies.
   */
  private function getSearchDescriptionTaxonomies($obj): string {
    $terms = [];
    $taxonomyIds = [];

    $elasticQuery = base64_decode($obj->elastic_query);
    $elasticQueryObject = json_decode($elasticQuery);

    // Free text search.
    $query = $elasticQueryObject->query->bool->must[1]->bool->should[1]->combined_fields->query ?? NULL;

    // Ammattiala / Task area.
    $externalTaxonomyIds = array_merge($taxonomyIds, $elasticQueryObject->query->bool->must[2]->terms->task_area_external_id ?? []);
    if (!empty($externalTaxonomyIds)) {
      $terms = $this->getLabelsByExternalId($externalTaxonomyIds, $obj->lang);
    }

    // Type of employment.
    $taxonomyIds = array_merge($taxonomyIds, $elasticQueryObject->query->bool->must[3]->bool->should[1]->terms->employment_type_id ?? []);
    if (!empty($taxonomyIds)) {
      $employmentTermLabels = $this->getLabelsByTermIds($taxonomyIds, $obj->lang);
    }

    // Job location:
    $area_filters = $this->extractQueryParameters($obj->query, 'area_filter');
    if (!empty($area_filters)) {
      // Duplicated from react frondend for proper translation mapping.
      $areasList = [
        'eastern' => 'Eastern area',
        'central' => 'Central area',
        'southern' => 'Southern area',
        'southeastern' => 'South-Eastern area',
        'western' => 'Western area',
        'northern' => 'Northern area',
        'northeast' => 'North-Eastern area',
      ];

      foreach ($area_filters as $area) {
        $areaFiltersTranslated[] = $this->translateString($areasList[$area], $obj->lang);
      }
    }

    // Build description string:
    $description = '';

    // Search term first.
    array_unshift($terms, $query);

    $allTerms = array_merge($terms, $areaFiltersTranslated);
    if (!empty($allTerms)) {
      $description .= implode(', ', array_filter($allTerms));
    }

    if (!empty($employmentTermLabels)) {
      if (!empty($description)) {
        $description .= ', ';
      }
      $description .= implode(' / ', $employmentTermLabels);
    }

    if (empty($description)) {
      '*';
    }

    return $description;
  }

  /**
   * A method to handle the POST request for subscription.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response based on the subscription request.
   */
  public function post(): JsonResponse {
    $request = $this->requestStack->getCurrentRequest();
    $body = $request->getContent(FALSE);
    $bodyObj = json_decode($body);
    $bodyObj->search_description = $this->getSearchDescriptionTaxonomies($bodyObj);

    $token = $request->headers->get('token');

    // FIXME: somehow, we would need to validate token from
    // /session/token from react
    // side, but there's just no way to match it at backend?!
    // $csrfTokenService = $this->container->get('csrf_token');
    // $expectedToken = $csrfTokenService->get('session');
    // if ($this->csrfTokenService->validate($token, 'session') === FALSE) {
    //
    // }.
    $client = new Client();
    $hakuvahtiServer = getenv('HAKUVAHTI_URL');
    $response = $client->request('POST', $hakuvahtiServer . '/subscription', [
      RequestOptions::JSON => $bodyObj,
      RequestOptions::HEADERS => [
        'token' => $token,
        'Content-Type' => 'application/json',
      ],
    ]);

    $statusCode = $response->getStatusCode();

    if ($statusCode >= 200 && $statusCode < 300) {
      return new JsonResponse(['success' => TRUE], Response::HTTP_OK);
    }
    else {
      return new JsonResponse(['success' => FALSE, 'error' => $response->getBody()->getContents()], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

}
