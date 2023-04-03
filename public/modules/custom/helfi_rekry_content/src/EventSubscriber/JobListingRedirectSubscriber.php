<?php

declare(strict_types = 1);

namespace Drupal\helfi_rekry_content\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\EventSubscriber\HttpExceptionSubscriberBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

/**
 * Redirect job listing 403s for anonymous users.
 */
class JobListingRedirectSubscriber extends HttpExceptionSubscriberBase {

  /**
   * Constructs a new Redirect403Subscriber.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected AccountInterface $currentUser
  ) {}

  /**
   * {@inheritdoc}
   */
  protected function getHandledFormats(): array {
    return ['html'];
  }

  /**
   * Redirects on 403 Access Denied kernel exceptions.
   *
   * @param \Symfony\Component\HttpKernel\Event\ExceptionEvent $event
   *   The Event to process.
   */
  public function on403(ExceptionEvent $event): void {
    if (!$this->currentUser->isAnonymous()) {
      return;
    }

    $request = $event->getRequest();
    $node = $request->attributes->get('node');
    if (
      !$node instanceof NodeInterface ||
      $node->bundle() !== 'job_listing'
    ) {
      return;
    }

    $config = $this->configFactory->get('helfi_rekry_content.job_listings');
    $redirectNode = $config->get('redirect_403_page');
    if (!$redirectNode) {
      return;
    }

    $url = Url::fromRoute('entity.node.canonical', ['node' => $redirectNode])->toString();

    // Set temporary redirect.
    $response = new RedirectResponse($url, 307);
    $event->setResponse($response);
  }

}
