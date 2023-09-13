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

  /**
   * If trying to access non-existing translation, redirect to existing one.
   *
   * @param \Symfony\Component\HttpKernel\Event\ExceptionEvent $event
   *   The Event to process.
   */
  public function on404(ExceptionEvent $event) {
    $uri = $event->getRequest()->getRequestUri();
    $redirectFrom = 'avoimet-tyopaikat/avoimet-tyopaikat/';
    if (!str_contains($uri, $redirectFrom)) {
      return;
    }

    $recruitmentId = array_reverse(explode('/', $uri))[0];
    $nodes = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties(['field_recruitment_id' => $recruitmentId]);

    if (!$nodes || !$node = reset($nodes)) {
      return;
    }

    // Since we are listening to 404 exception,
    // the node loaded is automatically existing translation.
    // We can just redirect without worrying whether the translation exists.
    $response = new RedirectResponse(
      $node->toUrl('canonical', ['language' => $node->language()])->toString(),
      302
    );
    $event->setResponse($response);
  }

}
