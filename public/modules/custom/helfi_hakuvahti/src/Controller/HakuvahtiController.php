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
final class HakuvahtiController extends ControllerBase implements LoggerAwareInterface {

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
      // Check subscription status first.
      $status = $this->hakuvahti->getStatus($hash, $subscription);

      // Already confirmed.
      if ($status === 'active') {
        return [
          '#theme' => 'hakuvahti_confirmation',
          '#title' => $this->t('Saved search already confirmed', [], ['context' => 'Hakuvahti']),
          '#message' => [
            $this->t('You have already confirmed this saved search.', [], ['context' => 'Hakuvahti']),
            $this->t('You will receive email alerts about new search results up to once a day.', [], ['context' => 'Hakuvahti']),
            $this->t('Each email contains an unsubscribe link that you can use to unsubscribe from saved search alerts. You can save a new search at any time.', [], ['context' => 'Hakuvahti']),
          ],
        ];
      }

      // Status is 'inactive' - proceed with confirmation.
      if ($status === 'inactive') {
        $this->hakuvahti->confirm($hash, $subscription);

        return [
          '#theme' => 'hakuvahti_confirmation',
          '#title' => $this->t('Search saved successfully', [], ['context' => 'Hakuvahti']),
          '#message' => [
            $this->t('You will receive email alerts about new search results up to once a day.', [], ['context' => 'Hakuvahti']),
            $this->t('Each email contains an unsubscribe link that you can use to unsubscribe from saved search alerts. You can save a new search at any time.', [], ['context' => 'Hakuvahti']),
            $this->t('Each saved search is valid for 6 months.', [], ['context' => 'Hakuvahti']),
          ],
        ];
      }
    }
    catch (HakuvahtiException $exception) {
      if ($exception->getCode() === 404) {
        $this->logger?->info('Hakuvahti confirmation request failed: ' . $exception->getMessage());
      }
      else {
        $this->logger?->error('Hakuvahti confirmation request failed: ' . $exception->getMessage());
      }
    }

    return [
      '#theme' => 'hakuvahti_confirmation',
      '#title' => $this->t('Confirmation of saved search failed', [], ['context' => 'Hakuvahti']),
      '#message' => $this->t('The confirmation of your saved search failed. You can try confirming your saved search again from your email.', [], ['context' => 'Hakuvahti']),
    ];
  }

  /**
   * Handles the renewal of a saved search.
   *
   * @return array
   *   A render array for the renewal form.
   */
  public function renew(Request $request): array {
    $hash = $request->query->get('hash');
    $subscription = $request->query->get('subscription');

    if ($request->isMethod('POST')) {
      return $this->handleRenewFormSubmission($hash, $subscription);
    }

    return [
      '#theme' => 'hakuvahti_form',
      '#title' => $this->t('Renewing saved search', [], ['context' => 'Hakuvahti']),
      '#message' => $this->t('Please wait while the saved search is being renewed.', [], ['context' => 'Hakuvahti']),
      '#button_text' => $this->t('Renew saved search', [], ['context' => 'Hakuvahti']),
      '#autosubmit' => TRUE,
      '#action_url' => Url::fromRoute('helfi_hakuvahti.renew', [], [
        'query' => [
          'hash' => $hash,
          'subscription' => $subscription,
        ],
      ]),
    ];
  }

  /**
   * Handles the renewal form submission.
   *
   * @param string $hash
   *   The hash parameter.
   * @param string $subscription
   *   The subscription parameter.
   *
   * @return array
   *   A render array for the renewal result.
   */
  private function handleRenewFormSubmission(string $hash, string $subscription): array {
    try {
      $this->hakuvahti->renew($hash, $subscription);

      return [
        '#theme' => 'hakuvahti_confirmation',
        '#title' => $this->t('Search renewed successfully', [], ['context' => 'Hakuvahti']),
        '#message' => $this->t('Your saved search has been renewed.', [], ['context' => 'Hakuvahti']),
      ];
    }
    catch (HakuvahtiException $exception) {
      // 404 error is returned if:
      // * Submission has been deleted after it expired.
      // * Submission does not exist.
      if ($exception->getCode() === 404) {
        $this->logger?->info('Hakuvahti renewal request failed: ' . $exception->getMessage());
      }
      else {
        $this->logger?->error('Hakuvahti renewal request failed: ' . $exception->getMessage());
      }
    }

    return [
      '#theme' => 'hakuvahti_confirmation',
      '#title' => $this->t('Renewal failed', [], ['context' => 'Hakuvahti']),
      '#message' => $this->t('Renewing saved search failed. Please try again.', [], ['context' => 'Hakuvahti']),
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
        '#title' => $this->t('Saved search deleted', [], ['context' => 'Hakuvahti']),
        '#message' => [
          $this->t('The saved search was successfully deleted.', [], ['context' => 'Hakuvahti']),
          $this->t('You can save more searches at any time.', [], ['context' => 'Hakuvahti']),
        ],
        '#link' => Link::fromTextAndUrl($this->t('Save a new search for jobs', [], ['context' => 'Hakuvahti']), Url::fromUri('internal:/')),
      ];
    }
    catch (HakuvahtiException $exception) {
      $this->logger?->error('Hakuvahti unsubscribe request failed: ' . $exception->getMessage());

      return [
        '#theme' => 'hakuvahti_confirmation',
        '#title' => $this->t('Failed to delete saved search', [], ['context' => 'Hakuvahti']),
        '#message' => $this->t('Failed to delete saved search. You can try deleting the saved search again from your email.', [], ['context' => 'Hakuvahti']),
      ];
    }
  }

}
