<?php

declare(strict_types=1);

namespace Drupal\helfi_hakuvahti\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\Token;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Unsubscribes from a subscription.
 */
final class HelfiHakuvahtiUnsubscribeController extends ControllerBase {

  /**
   * Constructor for the class.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The HTTP client.
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \App\Services\Token $tokenService
   *   The token service.
   * @param \App\Interfaces\AccountInterface $user
   *   The current user.
   */
  public function __construct(
    protected ClientInterface $httpClient,
    protected ContainerInterface $container,
    protected RequestStack $requestStack,
    protected Token $tokenService,
    protected AccountInterface $user
  ) {}

  /**
   * Returns the form ID for unsubscribing from a subscription.
   *
   * @return string
   *   The Form name
   */
  public function getFormId() {
    return 'hakuvahti_unsubscribe_form';
  }

  /**
   * Executes the confirmation process for a saved search.
   *
   * @return array
   *   The build array containing the confirmation form or the
   *   saved search form.
   */
  public function __invoke(): array {
    $request = $this->requestStack->getCurrentRequest();
    $hash = $request->query->get('hash');
    $subscription = $request->query->get('subscription');

    if ($this->isFormSubmitted()) {
      return $this->handleFormSubmission($hash, $subscription);
    }

    return $this->buildForm();
  }

  /**
   * Handles the form submission for unsubscribing from a subscription.
   *
   * @param string $hash
   *   Description of the hash parameter.
   * @param string $subscription
   *   Description of the subscription parameter.
   *
   * @return array
   *   The build array containing the confirmation form
   *   or the failed submission form.
   */
  private function handleFormSubmission(string $hash, string $subscription): array {
    if ($this->sendUnsubscribeRequest($hash, $subscription)) {
      return $this->buildConfirmation();
    }

    return $this->buildFailedSubmission();
  }

  /**
   * Builds the form for deleting a saved search.
   *
   * @return array
   *   The build array containing the form
   *   structure for deleting a saved search.
   */
  private function buildForm(): array {
    $build = [];

    $build['form'] = [
      '#type' => 'form',
      '#attributes' => [
        'class' => ['page-title'],
      ],
      '#id' => $this->getFormId(),
      '#form_id' => $this->getFormId(),
      '#action' => $this->getFormActionUrl(),
      '#method' => 'POST',
    ];

    $build['form']['paragraph'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Please confirm that you wish to delete the saved search. If you have other searches saved on the City website, this link will not delete them.'),
    ];

    $build['form']['button'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete saved search'),
      '#attributes' => [
        'class' => ['my-button'],
      ],
    ];

    return $build;
  }

  /**
   * Builds the confirmation array for the saved search deletion.
   *
   * @return array
   *   The build array containing the confirmation details.
   */
  private function buildConfirmation(): array {
    $build = [];

    $build['confirmation'] = [
      '#title' => $this->t('Saved search deleted'),
    ];

    $build['confirmation']['paragraph'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('The saved search has been deleted'),
      '#attributes' => [
        'class' => ['page-title'],
      ],
    ];

    $build['confirmation']['paragraph2'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('You can save more searches at any time.'),
      '#attributes' => [
        'class' => ['page-title'],
      ],
    ];

    $build['confirmation']['link'] = [
      '#type' => 'link',
      '#tag' => 'a',
      '#title' => $this->t('Return to open jobs front page'),
      '#url' => Url::fromUri('internal:/'),
    ];

    return $build;
  }

  /**
   * Builds the form for a failed submission when deleting a saved search.
   *
   * @return array
   *   The build array containing the form structure for a failed submission.
   */
  private function buildFailedSubmission(): array {
    $build = [];

    $build['form']['paragraph'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Deleting saved search failed. Please try again.'),
      '#attributes' => [
        'class' => ['page-title'],
      ],
    ];

    return $build;
  }

  /**
   * Returns the URL for the current form action.
   *
   * @return string
   *   The URL for the current form action.
   */
  protected function getFormActionUrl(): string {
    return $this->requestStack->getCurrentRequest()->getUri();
  }

  /**
   * Checks if the form is submitted via the POST method.
   *
   * @return bool
   *   Whether the form is submitted via the POST method.
   */
  protected function isFormSubmitted(): bool {
    $request = $this->requestStack->getCurrentRequest();
    return $request->isMethod('POST');
  }

  /**
   * Sends an unsubscribe request to the server.
   *
   * @param string $hash
   *   The hash of the subscription.
   * @param string $subscription
   *   The subscription ID.
   *
   * @return bool
   *   Returns TRUE if the request is successful, FALSE otherwise.
   */
  protected function sendUnsubscribeRequest(string $hash, string $subscription): bool {
    $csrfTokenService = $this->container->get('csrf_token');
    $expectedToken = $csrfTokenService->get('session');

    $httpClient = new Client();
    $options = [
      'headers' => [
        'Content-Type' => 'application/json',
        'token' => $expectedToken,
      ],
    ];

    $hakuvahtiServer = getenv('HAKUVAHTI_URL');

    try {
      $target_url = $hakuvahtiServer . '/subscription/delete/' . $subscription . '/' . $hash;
      $response = $httpClient->delete($target_url, $options);

      return $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;
    }
    catch (RequestException $exception) {
      return FALSE;
    }
  }

}
