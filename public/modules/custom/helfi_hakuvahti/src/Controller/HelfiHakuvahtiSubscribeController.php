<?php

declare(strict_types=1);

namespace Drupal\helfi_hakuvahti\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\helfi_api_base\Environment\EnvironmentResolverInterface;
use Drupal\helfi_hakuvahti\Event\SubscriptionAlterEvent;
use Drupal\helfi_hakuvahti\Event\SubscriptionEvent;
use Drupal\helfi_hakuvahti\HakuvahtiRequest;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Creates new subscription.
 */
final class HelfiHakuvahtiSubscribeController extends ControllerBase implements LoggerAwareInterface {

  use LoggerAwareTrait;

  public function __construct(
    protected RequestStack $requestStack,
    private readonly ClientInterface $client,
    private readonly EventDispatcherInterface $eventDispatcher,
    private readonly EnvironmentResolverInterface $environmentResolver,
  ) {
  }

  /**
   * A method to handle the POST request for subscription.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response based on the subscription request.
   */
  public function post(Request $request): JsonResponse {
    if (!$hakuvahtiServer = getenv('HAKUVAHTI_URL')) {
      $this->logger?->error('Hakuvahti is missing a required HAKUVAHTI_URL configuration.');
      return new JsonResponse(['success' => FALSE, 'error' => 'Unable to process the request.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    try {
      $requestData = json_decode($request->getContent(), TRUE, flags: JSON_THROW_ON_ERROR);

      if (!isset($requestData['site_id'])) {
        $requestData['site_id'] = $this->environmentResolver->getActiveProject()->getName();
      }

      $requestObject = new HakuvahtiRequest($requestData);
    }
    catch (\InvalidArgumentException | \JsonException $e) {
      // The frontend should not send invalid requests.
      $this->logger?->error('Hakuvahti initial subscription failed: ' . $e->getMessage());
      return new JsonResponse(['success' => FALSE, 'error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
    }

    // Allows other modules to alter the request.
    $requestEvent = new SubscriptionAlterEvent($requestObject);
    $this->eventDispatcher->dispatch($requestEvent);

    try {
      $this->client->request('POST', "$hakuvahtiServer/subscription", [
        RequestOptions::JSON => $requestEvent->getHakuvahtiRequest()->getServiceRequestData(),
        RequestOptions::HEADERS => [
          // @todo hakuvahti has no use for Drupal tokens https://github.com/City-of-Helsinki/helfi-hakuvahti/blob/main/src/plugins/token.ts#L19.
          // Maybe this value could be kind of api-key, so
          // that only allowed services can talk to hakuvahti?
          'token' => '123',
          'Content-Type' => 'application/json',
        ],
      ]);

      // Notify other modules about the subscription.
      $this->eventDispatcher->dispatch(new SubscriptionEvent($requestObject));
    }
    catch (GuzzleException $e) {
      $this->logger?->error("Unable to send Hakuvahti-request - Code {$e->getCode()}: {$e->getMessage()}");
      return new JsonResponse(['success' => FALSE, 'error' => 'Error while handling the request.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    return new JsonResponse(['success' => TRUE], Response::HTTP_OK);
  }

}
