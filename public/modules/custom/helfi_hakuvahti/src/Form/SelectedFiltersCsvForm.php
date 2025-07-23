<?php

declare(strict_types=1);

namespace Drupal\helfi_hakuvahti\Form;

use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\helfi_hakuvahti\HakuvahtiTracker;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * The form which allows admin to load selected filters as csv-file.
 */
final class SelectedFiltersCsvForm extends FormBase {

  use AutowireTrait;

  /**
   * The constructor.
   *
   * @param \Drupal\helfi_hakuvahti\HakuvahtiTracker $tracker
   *   The hakuvahti tracker.
   */
  public function __construct(
    private readonly HakuvahtiTracker $tracker,
  ) {
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'helfi_hakuvahti_csv_download_form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $first_entry = $this->tracker->getFirstEntry();
    $date = $first_entry ? (new \DateTime($first_entry->created_at))->format('Y-m-d') : NULL;

    $form['from'] = [
      '#type' => 'date',
      '#date_timezone' => 'Europe/Helsinki',
      '#date_format' => 'd-m-Y',
      '#title' => $this->t('Start date'),
      '#default_value' => $date,
      '#required' => TRUE,
    ];

    $form['to'] = [
      '#type' => 'date',
      '#date_timezone' => 'Europe/Helsinki',
      '#date_format' => 'Y-m-d',
      '#title' => $this->t('End date'),
      '#default_value' => date('Y-m-d'),
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Download'),
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    try {
      $from = new \DateTime($form_state->getValue('from'));
      $to = new \DateTime($form_state->getValue('to'));
    }
    catch (\Exception $e) {
      $this->handleError($this->t('Unable to handle the given date'), $form_state);
      return;
    }

    if ($from > $to) {
      $this->handleError($this->t('Start date cannot be after End date'), $form_state);
      return;
    }

    try {
      $rows = $this->tracker->getSavedFilters($from, $to);
      if (!$rows) {
        $this->handleError($this->t('No results found'), $form_state);
        return;
      }

      $csv_string = $this->tracker->createCsvStringFromArray($rows);
    }
    catch (\Exception $e) {
      $this->handleError($this->t('Something went wrong: @message', ['@message' => $e->getMessage()]), $form_state);
      return;
    }

    $date = date('Y-m-d');
    $filename = "hakuvahti-$date.csv";

    $response = new Response();
    $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
    $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s"', $filename));
    $response->setContent($csv_string);

    $form_state->setResponse($response);
  }

  /**
   * Handle error.
   *
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $message
   *   Message to show.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  private function handleError(TranslatableMarkup $message, FormStateInterface $form_state): void {
    $this->messenger()->addError($message);
    $response = new RedirectResponse(Url::fromRoute($this->getRouteMatch()->getRouteName())->toString(), 301);
    $form_state->setResponse($response);
  }

}
