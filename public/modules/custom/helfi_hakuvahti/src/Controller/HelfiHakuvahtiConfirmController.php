<?php

declare(strict_types=1);

namespace Drupal\helfi_hakuvahti\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Utility\Token;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Confirms a subscription.
 */
final class HelfiHakuvahtiConfirmController extends ControllerBase {

  use StringTranslationTrait;

  /**
   * Constructor for HelfiHakuvahtiConfirmController.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The HTTP client.
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Core\Utility\Token $tokenService
   *   The token service.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user account.
   */
  public function __construct(
    protected ClientInterface $httpClient,
    protected ContainerInterface $container,
    protected RequestStack $requestStack,
    protected Token $tokenService,
    protected AccountInterface $user,
  ) {}

  /**
   * Returns the form ID for the confirmation form.
   *
   * @return string
   *   The Form name
   */
  public function getFormId() {
    return 'hakuvahti_confirm_form';
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
   * Handles the form submission for confirming a subscription.
   *
   * @param mixed $hash
   *   The hash parameter.
   * @param mixed $subscription
   *   The subscription parameter.
   *
   * @return array
   *   The build array containing the confirmation success or failure.
   */
  private function handleFormSubmission($hash, $subscription): array {
    if ($this->sendConfirmationRequest($hash, $subscription)) {
      return $this->buildConfirmationSuccess();
    }

    return $this->buildConfirmationFailure();
  }

  /**
   * Builds the form for confirming a saved search.
   *
   * @return array
   *   The build array containing the form structure
   *   for confirming a saved search.
   */
  private function buildForm(): array {
    $build = [];

    $build['form'] = [
      '#type' => 'form',
      '#id' => $this->getFormId(),
      '#form_id' => $this->getFormId(),
      '#action' => $this->getFormActionUrl(),
      '#method' => 'POST',
    ];

    $build['#title'] = $this->t('Confirm saved search', [], ['context' => 'Hakuvahti']);

    $build['form']['paragraph'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Please confirm the saved search to receive notifications. Click on the button below.', [], ['context' => 'Hakuvahti']),
    ];

    $build['form']['divider'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => ['page-divider'],
      ],
    ];

    $build['form']['button'] = [
      '#type' => 'submit',
      '#value' => $this->t('Confirm saved search', [], ['context' => 'Hakuvahti']),
    ];

    return $build;
  }

  /**
   * Builds the confirmation array for a successful saved search confirmation.
   *
   * @return array
   *   Success form
   */
  private function buildConfirmationSuccess(): array {
    $build = [];

    $build['#title'] = $this->t('Search saved successfully', [], ['context' => 'Hakuvahti']);

    $build['confirmation'] = [
      '#type' => 'html_tag',
      '#tag' => 'article',
      '#attributes' => [
        'class' => ['hakuvahti-confirmation'],
      ],
    ];

    $build['confirmation']['components'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => ['components'],
      ],
    ];

    $build['confirmation']['components']['component'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => ['component'],
      ],
    ];

    $build['confirmation']['components']['component']['paragraph'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('You will receive an email notification of any new results matching your saved search criteria. You can delete the saved search via the cancellation link in the email messages.', [], ['context' => 'Hakuvahti']),
    ];

    $build['confirmation']['components']['component']['divider'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => ['page-divider'],
      ],
    ];

    return $build;
  }

  /**
   * Builds the confirmation array for a failed saved search confirmation.
   *
   * @return array
   *   Failure form
   */
  private function buildConfirmationFailure(): array {
    $build = [];

    $build['#title'] = $this->t('Confirmation failed', [], ['context' => 'Hakuvahti']);

    $build['confirmation'] = [
      '#type' => 'html_tag',
      '#tag' => 'article',
      '#attributes' => [
        'class' => ['hakuvahti-confirmation'],
      ],
    ];

    $build['confirmation']['components'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => ['components'],
      ],
    ];

    $build['confirmation']['components']['component'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => ['component'],
      ],
    ];

    $build['confirmation']['components']['component']['paragraph'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Confirming saved search failed. Please try again.', [], ['context' => 'Hakuvahti']),
    ];

    $build['confirmation']['components']['component']['divider'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => ['page-divider'],
      ],
    ];

    return $build;
  }

  /**
   * Retrieves the form action URL from the current request.
   *
   * @return string
   *   The URL of the current request.
   */
  protected function getFormActionUrl(): string {
    return $this->requestStack->getCurrentRequest()->getUri();
  }

  /**
   * Checks if the form is submitted via POST method.
   *
   * @return bool
   *   Whether the form is submitted via POST method.
   */
  protected function isFormSubmitted(): bool {
    $request = $this->requestStack->getCurrentRequest();
    return $request->isMethod('POST');
  }

  /**
   * Sends a confirmation request to the Hakuvahti server.
   *
   * @param string $subscriptionHash
   *   The subscription hash.
   * @param string $subscriptionId
   *   The subscription ID.
   *
   * @return bool
   *   Returns TRUE if the confirmation request is successful, FALSE otherwise.
   */
  protected function sendConfirmationRequest(string $subscriptionHash, string $subscriptionId): bool {
    $csrfTokenService = $this->container->get('csrf_token');
    $expectedToken = $csrfTokenService->get('session');
    $httpClient = new Client([
      'headers' => [
        'Content-Type' => 'application/json',
        'token' => $expectedToken,
      ],
    ]);

    $hakuvahtiServer = getenv('HAKUVAHTI_URL');
    $targetUrl = $hakuvahtiServer . '/subscription/confirm/' . $subscriptionId . '/' . $subscriptionHash;

    try {
      $response = $httpClient->get($targetUrl);
      return $response->getBody()->getContents() !== '';
    }
    catch (RequestException $exception) {
      return FALSE;
    }
  }

}
