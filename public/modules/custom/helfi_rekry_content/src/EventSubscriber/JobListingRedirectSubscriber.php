<?php

declare(strict_types = 1);

namespace Drupal\helfi_rekry_content\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\EventSubscriber\HttpExceptionSubscriberBase;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
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
    protected AccountInterface $currentUser,
    protected EntityTypeManager $entityTypeManager,
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
    $response = new TrustedRedirectResponse($url, 307);
    $event->setResponse($response);
  }

  /**
   * If trying to access non-existing translation, redirect to existing one.
   *
   * @param ExceptionEvent $event
   *   The Event to process.
   */
  public function on404(ExceptionEvent $event) : void {
    $uri = $event->getRequest()->getRequestUri();
    $redirectPaths = [
      'avoimet-tyopaikat/avoimet-tyopaikat/',
      'lediga-jobb/lediga-jobb/',
      'open-jobs/open-jobs/',
    ];

    $redirectFrom = NULL;
    foreach($redirectPaths as $path) {
      if (str_contains($uri, $path)) {
        $redirectFrom = $path;
        break;
      }
    }

    if (!$redirectFrom) {
      return;
    }

    $recruitmentId = array_reverse(explode('/', $uri))[0];
    $nodes = $this->entityTypeManager
      ->getStorage('node')
      ->loadByProperties(['field_recruitment_id' => $recruitmentId]);

    if (!$nodes || !$node = reset($nodes)) {
      return;
    }

    $url = $node->toUrl('canonical', ['language' => $node->language()])->toString();
    $response = new TrustedRedirectResponse($url);
    $response->addCacheableDependency($url);
    $event->setResponse($response);
  }

}
