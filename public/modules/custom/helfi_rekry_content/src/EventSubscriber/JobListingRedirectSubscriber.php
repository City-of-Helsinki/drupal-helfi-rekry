<?php

declare(strict_types=1);

namespace Drupal\helfi_rekry_content\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\EventSubscriber\HttpExceptionSubscriberBase;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

/**
 * Http exception event subscribers for job listings.
 */
class JobListingRedirectSubscriber extends HttpExceptionSubscriberBase {

  /**
   * Constructs a new Redirect403Subscriber.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected AccountInterface $currentUser,
    protected EntityTypeManagerInterface $entityTypeManager,
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

    // Set status code to 410.
    $response = new TrustedRedirectResponse($url);
    $response->setStatusCode(410);
    $event->setResponse($response);
  }

  /**
   * The 404 exception listener.
   *
   * #UHF8946 External service's automation is only capable of creating links to
   * finnish job listings. If finnish translation doesn't exist the user will be
   * automatically redirected to existing translation with matching job ID.
   *
   * @param \Symfony\Component\HttpKernel\Event\ExceptionEvent $event
   *   The Event to process.
   */
  public function on404(ExceptionEvent $event) : void {
    $uri = $event->getRequest()->getRequestUri();
    $redirectPaths = [
      'fi' => 'avoimet-tyopaikat/avoimet-tyopaikat/',
      'sv' => 'lediga-jobb/lediga-jobb/',
      'en' => 'open-jobs/open-jobs/',
    ];

    $redirectFrom = NULL;
    foreach ($redirectPaths as $path) {
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
