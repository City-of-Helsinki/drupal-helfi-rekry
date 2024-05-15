<?php

declare(strict_types=1);

namespace Drupal\helfi_hakuvahti\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Language\LanguageManagerInterface;
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
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The CSRF token service.
   *
   * @var \Drupal\Core\CsrfToken\CsrfTokenManagerInterface
   */
  protected $csrfTokenService;

  /**
   * Constructor for the HelfiHakuvahtiSubscribeController class.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   */
  public function __construct(ContainerInterface $container, RequestStack $requestStack, LanguageManagerInterface $languageManager) {
    $this->csrfTokenService = $container->get('csrf_token');
    $this->requestStack = $requestStack;
    $this->languageManager = $languageManager;
  }

  private function getSearchDescriptionTaxonomies(string $elastic_query): string {
    error_log($elastic_query);

    return "";
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
    $bodyObj->lang = $this->languageManager->getCurrentLanguage()->getId();
    $bodyObj->search_description = $this->getSearchDescriptionTaxonomies($bodyObj->elastic_query);

    $token = $request->headers->get('token');

    // FIXME: somehow, we would need to validate token from
    // /session/token from react
    // side, but there's just no way to match it at backend?!
    // $expectedToken = $this->csrfTokenService->get('session');
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
