<?php

declare(strict_types=1);

namespace Drupal\helfi_hakuvahti\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\helfi_hakuvahti\HakuvahtiException;
use Drupal\helfi_hakuvahti\HakuvahtiInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for handling Hakuvahti confirmations and unsubscriptions.
 */
final class HelfiHakuvahtiController extends ControllerBase implements LoggerAwareInterface {

  use StringTranslationTrait;
  use LoggerAwareTrait;

  public function __construct(
    protected readonly HakuvahtiInterface $hakuvahti,
  ) {
  }

  /**
   * Handles the confirmation of a saved search.
   *
   * @return array
   *   A render array for the confirmation form.
   */
  public function confirm(Request $request): array {
    $hash = $request->query->get('hash');
    $subscription = $request->query->get('subscription');

    if ($request->isMethod('POST')) {
      return $this->handleConfirmFormSubmission($hash, $subscription);
    }

    return [
      '#theme' => 'hakuvahti_form',
      '#title' => $this->t('Enabling saved search', [], ['context' => 'Hakuvahti']),
      '#message' => $this->t('Please wait while the saved search is being enabled.', [], ['context' => 'Hakuvahti']),
      '#button_text' => $this->t('Confirm saved search', [], ['context' => 'Hakuvahti']),
      '#autosubmit' => TRUE,
      '#action_url' => Url::fromRoute('helfi_hakuvahti.confirm', [], [
        'query' => [
          'hash' => $hash,
          'subscription' => $subscription,
        ],
      ]),
    ];
  }

  /**
   * Handles the activation form submission.
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
    try {
      $this->hakuvahti->confirm($hash, $subscription);

      return [
        '#theme' => 'hakuvahti_confirmation',
        '#title' => $this->t('Search saved successfully', [], ['context' => 'Hakuvahti']),
        '#message' => $this->t('You will receive an email notification of any new results matching your saved search criteria. You can delete the saved search via the cancellation link in the email messages.', [], ['context' => 'Hakuvahti']),
      ];
    }
    catch (HakuvahtiException $exception) {
      // 404 error is returned if:
      // * Submission has been deleted after it expired.
      // * Submission has already been confirmed.
      // * Submission does not exist.
      if ($exception->getCode() === 404) {
        $this->logger?->info('Hakuvahti confirmation request failed: ' . $exception->getMessage());
      }
      else {
        $this->logger?->error('Hakuvahti confirmation request failed: ' . $exception->getMessage());
      }
    }

    return [
      '#theme' => 'hakuvahti_confirmation',
      '#title' => $this->t('Confirmation failed', [], ['context' => 'Hakuvahti']),
      '#message' => $this->t('Confirming saved search failed. Please try again.', [], ['context' => 'Hakuvahti']),
    ];
  }

  /**
   * Handles the unsubscription from a saved search.
   *
   * @return array
   *   A render array for the unsubscription form.
   */
  public function unsubscribe(Request $request): array {
    $hash = $request->query->get('hash');
    $subscription = $request->query->get('subscription');

    if ($request->isMethod('POST')) {
      return $this->handleUnsubscribeFormSubmission($hash, $subscription);
    }

    return [
      '#theme' => 'hakuvahti_form',
      '#title' => $this->t('Deleting saved search', [], ['context' => 'Hakuvahti']),
      '#message' => $this->t('Please wait while the saved search is being deleted. If you have other searches saved on the City website, this link will not delete them.', [], ['context' => 'Hakuvahti']),
      '#button_text' => $this->t('Delete saved search', [], ['context' => 'Hakuvahti']),
      '#autosubmit' => TRUE,
      '#action_url' => new Url('helfi_hakuvahti.unsubscribe', [], [
        'query' => [
          'hash' => $hash,
          'subscription' => $subscription,
        ],
      ]),
    ];
  }

  /**
   * Handles the unsubscribe form submission.
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
    try {
      $this->hakuvahti->unsubscribe($hash, $subscription);

      return [
        '#theme' => 'hakuvahti_confirmation',
        '#title' => $this->t('The saved search has been deleted', [], ['context' => 'Hakuvahti']),
        '#message' => $this->t('You can save more searches at any time.', [], ['context' => 'Hakuvahti']),
        '#link' => Link::fromTextAndUrl($this->t('Return to open jobs front page', [], ['context' => 'Hakuvahti']), Url::fromUri('internal:/')),
      ];
    }
    catch (HakuvahtiException $exception) {
      $this->logger?->error('Hakuvahti unsubscribe request failed: ' . $exception->getMessage());

      return [
        '#theme' => 'hakuvahti_confirmation',
        '#title' => $this->t('Deleting failed', [], ['context' => 'Hakuvahti']),
        '#message' => $this->t('Deleting saved search failed. Please try again.', [], ['context' => 'Hakuvahti']),
      ];
    }
  }

}
