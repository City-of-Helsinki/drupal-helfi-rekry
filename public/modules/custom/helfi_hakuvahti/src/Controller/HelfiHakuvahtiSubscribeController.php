<?php

declare(strict_types=1);

namespace Drupal\helfi_hakuvahti\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
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
    // Task area.
    $taxonomyIds = array_merge($taxonomyIds, $elasticQueryObject->query->bool->must[2]->terms->task_area_external_id ?? []);
    // Type of employment.
    $taxonomyIds = array_merge($taxonomyIds, $elasticQueryObject->query->bool->must[3]->bool->should[1]->terms->employment_type_id ?? []);

    if (!empty($taxonomyIds)) {
      $language = $obj->lang;
      $terms = array_map(function ($term) use ($language) {
        if ($term->hasTranslation($language)) {
          $translated_term = $term->getTranslation($language);
          return $translated_term->label();
        }
        return $term->label();
      }, $this->_entityTypeManager->getStorage('taxonomy_term')->loadMultiple($taxonomyIds));
    }

    // We need to send just *something* if nothing is selected in filters.
    if (empty($terms) && empty($query)) {
      $terms[] = '*';
    }

    array_unshift($terms, $query);

    return implode(', ', array_filter($terms));
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
    $bodyObj->lang = substr($bodyObj->query, 1, 2);
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
