<?php

declare(strict_types=1);

namespace Drupal\helfi_hakuvahti\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\Core\Utility\Token;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Controller for handling Hakuvahti subscription confirmations and unsubscriptions.
 */
final class HelfiHakuvahtiController extends ControllerBase {

  use StringTranslationTrait;

  /**
   * Constructs a HelfiHakuvahtiController object.
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
   *   The current user.
   */
  public function __construct(
    protected ClientInterface $httpClient,
    protected ContainerInterface $container,
    protected RequestStack $requestStack,
    protected Token $tokenService,
    protected AccountInterface $user,
  ) {}

  /**
   * Handles the confirmation of a saved search.
   *
   * @return array
   *   A render array for the confirmation form.
   */
  public function confirm(): array {
    $request = $this->requestStack->getCurrentRequest();
    $hash = $request->query->get('hash');
    $subscription = $request->query->get('subscription');

    return $this->isFormSubmitted() 
      ? $this->handleConfirmFormSubmission($hash, $subscription)
      : $this->buildConfirmForm();
  }

  /**
   * Handles the form submission for confirming a subscription.
   *
   * @param string $hash
   *   The hash parameter.
   * @param string $subscription
   *   The subscription parameter.
   *
   * @return array
   *   A render array for the confirmation result.
   */
  private function handleConfirmFormSubmission(string $hash, string $subscription): array {
    return $this->sendConfirmationRequest($hash, $subscription)
      ? $this->buildConfirmationSuccess()
      : $this->buildConfirmationFailure();
  }

  /**
   * Builds the form for confirming a saved search.
   *
   * @return array
   *   A render array for the confirmation form.
   */
  private function buildConfirmForm(): array {
    return [
      '#type' => 'form',
      '#id' => 'hakuvahti_confirm_form',
      '#form_id' => 'hakuvahti_confirm_form',
      '#theme' => 'hakuvahti_form',
      '#title' => $this->t('Confirm saved search', [], ['context' => 'Hakuvahti']),
      '#paragraph' => $this->t('Please confirm the saved search to receive notifications. Click on the button below.', [], ['context' => 'Hakuvahti']),
      '#button_text' => $this->t('Confirm saved search', [], ['context' => 'Hakuvahti']),
      '#action_url' => $this->getFormActionUrl(),
    ];
  }

  /**
   * Builds the confirmation success message.
   *
   * @return array
   *   A render array for the confirmation success message.
   */
  private function buildConfirmationSuccess(): array {
    return [
      '#theme' => 'hakuvahti_confirmation',
      '#title' => $this->t('Search saved successfully', [], ['context' => 'Hakuvahti']),
      '#message' => $this->t('You will receive an email notification of any new results matching your saved search criteria. You can delete the saved search via the cancellation link in the email messages.', [], ['context' => 'Hakuvahti']),
    ];
  }

  /**
   * Builds the confirmation failure message.
   *
   * @return array
   *   A render array for the confirmation failure message.
   */
  private function buildConfirmationFailure(): array {
    return [
      '#theme' => 'hakuvahti_failed_submission',
      '#title' => $this->t('Confirmation failed', [], ['context' => 'Hakuvahti']),
      '#message' => $this->t('Confirming saved search failed. Please try again.', [], ['context' => 'Hakuvahti']),
    ];
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
   *   TRUE if the confirmation request was successful, FALSE otherwise.
   */
  protected function sendConfirmationRequest(string $subscriptionHash, string $subscriptionId): bool {
    $csrfTokenService = $this->container->get('csrf_token');
    $httpClient = new Client([
      'headers' => [
        'Content-Type' => 'application/json',
        'token' => $csrfTokenService->get('session'),
      ],
    ]);

    try {
      $response = $httpClient->get(getenv('HAKUVAHTI_URL') . "/subscription/confirm/{$subscriptionId}/{$subscriptionHash}");
      return $response->getBody()->getContents() !== '';
    } catch (RequestException $exception) {
      return FALSE;
    }
  }

  /**
   * Handles the unsubscription from a saved search.
   *
   * @return array
   *   A render array for the unsubscription form.
   */
  public function unsubscribe(): array {
    $request = $this->requestStack->getCurrentRequest();
    $hash = $request->query->get('hash');
    $subscription = $request->query->get('subscription');

    return $this->isFormSubmitted()
      ? $this->handleUnsubscribeFormSubmission($hash, $subscription)
      : $this->buildUnsubscribeForm();
  }

  /**
   * Handles the form submission for unsubscribing from a subscription.
   *
   * @param string $hash
   *   The hash parameter.
   * @param string $subscription
   *   The subscription parameter.
   *
   * @return array
   *   A render array for the unsubscription result.
   */
  private function handleUnsubscribeFormSubmission(string $hash, string $subscription): array {
    return $this->sendUnsubscribeRequest($hash, $subscription)
      ? $this->buildUnsubscribeConfirmation()
      : $this->buildUnsubscribeFailedSubmission();
  }

  /**
   * Builds the form for unsubscribing from a saved search.
   *
   * @return array
   *   A render array for the unsubscription form.
   */
  private function buildUnsubscribeForm(): array {
    return [
      '#theme' => 'hakuvahti_form',
      '#title' => $this->t('Are you sure you wish to delete the saved search?', [], ['context' => 'Hakuvahti']),
      '#paragraph' => $this->t('Please confirm that you wish to delete the saved search. If you have other searches saved on the City website, this link will not delete them.', [], ['context' => 'Hakuvahti']),
      '#button_text' => $this->t('Delete saved search', [], ['context' => 'Hakuvahti']),
      '#action_url' => $this->getFormActionUrl(),
    ];
  }

  /**
   * Builds the unsubscription confirmation message.
   *
   * @return array
   *   A render array for the unsubscription confirmation message.
   */
  private function buildUnsubscribeConfirmation(): array {
    return [
      '#theme' => 'hakuvahti_confirmation',
      '#title' => $this->t('The saved search has been deleted', [], ['context' => 'Hakuvahti']),
      '#message' => $this->t('You can save more searches at any time.', [], ['context' => 'Hakuvahti']),
      '#link_text' => $this->t('Return to open jobs front page', [], ['context' => 'Hakuvahti']),
      '#link_url' => Url::fromUri('internal:/'),
    ];
  }

  /**
   * Builds the unsubscription failure message.
   *
   * @return array
   *   A render array for the unsubscription failure message.
   */
  private function buildUnsubscribeFailedSubmission(): array {
    return [
      '#theme' => 'hakuvahti_failed_submission',
      '#title' => $this->t('Deleting failed', [], ['context' => 'Hakuvahti']),
      '#message' => $this->t('Deleting saved search failed. Please try again.', [], ['context' => 'Hakuvahti']),
    ];
  }

  /**
   * Sends an unsubscribe request to the Hakuvahti server.
   *
   * @param string $hash
   *   The hash parameter.
   * @param string $subscription
   *   The subscription parameter.
   *
   * @return bool
   *   TRUE if the unsubscription request was successful, FALSE otherwise.
   */
  protected function sendUnsubscribeRequest(string $hash, string $subscription): bool {
    $csrfTokenService = $this->container->get('csrf_token');
    $httpClient = new Client([
      'headers' => [
        'Content-Type' => 'application/json',
        'token' => $csrfTokenService->get('session'),
      ],
    ]);

    try {
      $response = $httpClient->delete(getenv('HAKUVAHTI_URL') . "/subscription/delete/{$subscription}/{$hash}");
      return $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;
    } catch (RequestException $exception) {
      return FALSE;
    }
  }

  /**
   * Gets the form action URL.
   *
   * @return string
   *   The form action URL.
   */
  protected function getFormActionUrl(): string {
    return $this->requestStack->getCurrentRequest()->getUri();
  }

  /**
   * Checks if the form is submitted.
   *
   * @return bool
   *   TRUE if the form is submitted, FALSE otherwise.
   */
  protected function isFormSubmitted(): bool {
    return $this->requestStack->getCurrentRequest()->isMethod('POST');
  }
}
