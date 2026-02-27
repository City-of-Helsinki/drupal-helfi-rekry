<?php

declare(strict_types=1);

namespace Drupal\helfi_rekry_content\EventSubscriber;

use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ResponseSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() : array {
    $events[KernelEvents::RESPONSE][] = ['onResponse', -100];
    return $events;
  }

  /**
   * Adds Clear-Site-Data -header to response.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The event to respond to.
   */
  public function onResponse(ResponseEvent $event) : void {
    $response = $event->getResponse();
    $response->headers->set('Clear-Site-Data', 'cache');
    $event->setResponse($response);
  }

}
