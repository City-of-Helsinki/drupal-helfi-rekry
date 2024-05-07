<?php

declare(strict_types=1);

namespace Drupal\helfi_hakuvahti\Controller;

use Drupal\Core\Controller\ControllerBase;
use \Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use stdClass;

/**
 * Returns responses for Hakuvahti routes.
 */
final class HelfiHakuvahtiSubscribeController extends ControllerBase {

  protected $requestStack;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;
 
  public function __construct(RequestStack $requestStack, LanguageManagerInterface $languageManager) {
    $this->requestStack = $requestStack;
    $this->languageManager = $languageManager;
  }

  public function post(): JsonResponse {
    $request = $this->requestStack->getCurrentRequest();
    $method = $request->getMethod();
    $currentLanguage = $this->languageManager->getCurrentLanguage();

    if ($method !== 'POST') {
      return new JsonResponse(['success' => false], Response::HTTP_METHOD_NOT_ALLOWED);
    }

    $body = $request->getContent(false);
    $bodyObj = json_decode($body);
    $bodyObj->lang = $currentLanguage->getId();

    foreach ($bodyObj as $key => $value) {
      $jsonArray[$key] = $value;      
    }

    if (!$body) {
      throw new \LogicException('Expected request body to be non-null');
    }

    $client = new Client();
    try {
      $token = $request->headers->get('token');
      // FIXME: token is different in backend for some reason?
      // TODO: validate token when mismatch is fixed
      // $expectedToken = \Drupal::service('csrf_token')->get('session');

      $json = json_encode($bodyObj, JSON_PRETTY_PRINT);
      $requestOpts[RequestOptions::JSON] = $jsonArray;
      $requestOpts[RequestOptions::HEADERS] = [
        'token' => $token,
        'Content-Type' => 'application/json',
      ];
      $response = $client->request('POST', 'http://helfi-rekry.docker.so:3000/subscription', $requestOpts);

    } catch (\GuzzleHttp\Exception\GuzzleException $e) {
      error_log($e->getMessage());

      throw new \RuntimeException('Unexpected exception from Guzzle', 0, $e);
    }

    $statusCode = $response->getStatusCode();
    $content = $response->getBody()->getContents();

    if ($statusCode >= 200 && $statusCode < 300) {
      return new JsonResponse(['success' => true], Response::HTTP_OK);
    } else {
      error_log($content);

      return new JsonResponse(['success' => false, 'error' => $content], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

}
