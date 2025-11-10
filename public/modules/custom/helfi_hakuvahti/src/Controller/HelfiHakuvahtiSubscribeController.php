<?php

declare(strict_types=1);

namespace Drupal\helfi_hakuvahti\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\helfi_hakuvahti\Event\SubscriptionEvent;
use Drupal\helfi_hakuvahti\HakuvahtiRequest;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Creates new subscription.
 */
final class HelfiHakuvahtiSubscribeController extends ControllerBase {

  public function __construct(
    protected RequestStack $requestStack,
    private readonly ClientInterface $client,
    #[Autowire(service: 'logger.channel.helfi_hakuvahti')]
    private readonly LoggerInterface $logger,
    #[Autowire(service: 'event_dispatcher')]
    private readonly EventDispatcherInterface $eventDispatcher,
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
      $this->logger->error('Hakuvahti is missing a required HAKUVAHTI_URL configuration.');
      return new JsonResponse(['success' => FALSE, 'error' => 'Unable to process the request.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    $requestData = json_decode($request->getContent(), TRUE);

    if (!isset($requestData['site_id'])) {
      $requestData['site_id'] = getenv('PROJECT_NAME');
    }

    try {
      $requestObject = new HakuvahtiRequest($requestData);
    }
    catch (\InvalidArgumentException $e) {
      // The frontend should not send invalid requests.
      $this->logger->error('Hakuvahti initial subscription failed due to invalid argument: ' . $e->getMessage());
      return new JsonResponse(['success' => FALSE, 'error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
    }

    // @todo Validate token, maybe create simple token-feature for this
    // kind of feature.
    // Drupal's token does not currently work for unauthenticated user,
    // check issue https://www.drupal.org/node/1803712.
    $token = $request->headers->get('token');

    try {
      $this->client->request('POST', "$hakuvahtiServer/subscription", [
        RequestOptions::JSON => $requestObject->getServiceRequestData(),
        RequestOptions::HEADERS => [
          'token' => $token,
          'Content-Type' => 'application/json',
        ],
      ]);

      $event = new SubscriptionEvent($requestObject->getElasticQuery(), $requestObject->getQueryParameters());
      $this->eventDispatcher->dispatch($event);
    }
    catch (GuzzleException $e) {
      $this->logger->error("Unable to send Hakuvahti-request - Code {$e->getCode()}: {$e->getMessage()}");
      return new JsonResponse(['success' => FALSE, 'error' => 'Error while handling the request.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    return new JsonResponse(['success' => TRUE], Response::HTTP_OK);
  }

}
