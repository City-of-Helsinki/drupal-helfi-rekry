<?php

declare(strict_types=1);

namespace Drupal\helfi_hakuvahti\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\helfi_hakuvahti\Event\SubscriptionAlterEvent;
use Drupal\helfi_hakuvahti\Event\SubscriptionEvent;
use Drupal\helfi_hakuvahti\HakuvahtiException;
use Drupal\helfi_hakuvahti\HakuvahtiInterface;
use Drupal\helfi_hakuvahti\HakuvahtiRequest;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Creates new subscription.
 */
final class HakuvahtiSubscribeController extends ControllerBase implements LoggerAwareInterface {

  use LoggerAwareTrait;

  public function __construct(
    private readonly HakuvahtiInterface $hakuvahti,
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
    try {
      $requestData = json_decode($request->getContent(), TRUE, flags: JSON_THROW_ON_ERROR);

      // Get config ID from query parameter, default to 'default'.
      $configId = $request->query->get('config') ?? 'default';

      // Try to load configuration entity.
      /** @var \Drupal\helfi_hakuvahti\Entity\HakuvahtiConfig|null $config */
      $config = $this->entityTypeManager()
        ->getStorage('hakuvahti_config')
        ->load($configId);

      if (!$config) {
        throw new \InvalidArgumentException("Hakuvahti configuration '$configId' not found.");
      }

      // Use site_id from configuration entity.
      $requestData['site_id'] = $config->getSiteId();

      $requestObject = new HakuvahtiRequest($requestData);
    }
    catch (\InvalidArgumentException | \JsonException $e) {
      // The frontend should not send invalid requests.
      $this->logger?->error('Hakuvahti initial subscription failed: ' . $e->getMessage());
      return new JsonResponse(['success' => FALSE, 'error' => 'Error while handling the request.'], Response::HTTP_BAD_REQUEST);
    }

    // Allows other modules to alter the request.
    $requestEvent = new SubscriptionAlterEvent($requestObject);
    $this->eventDispatcher->dispatch($requestEvent);

    try {
      $this->hakuvahti->subscribe($requestEvent->getHakuvahtiRequest());

      // Notify other modules about the subscription.
      $this->eventDispatcher->dispatch(new SubscriptionEvent($requestObject));
    }
    catch (HakuvahtiException $e) {
      $this->logger?->error("Unable to send Hakuvahti-request - Code {$e->getCode()}: {$e->getMessage()}");
      return new JsonResponse(['success' => FALSE, 'error' => 'Error while handling the request.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    return new JsonResponse(['success' => TRUE], Response::HTTP_OK);
  }

}
