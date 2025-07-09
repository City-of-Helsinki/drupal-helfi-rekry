<?php

declare(strict_types=1);

namespace Drupal\helfi_hakuvahti\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * The form which allows admin to load selected filters as csv-file.
 */
final class SelectedFiltersCsvForm extends FormBase {

  public function __construct() {
  }

  public function getFormId() {
    return 'helfi_hakuvahti_csv_download_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['from'] = [
      '#type' => 'date',
      '#date_timezone' => 'Europe/Helsinki',
      '#date_format' => 'd-m-Y',
      // '#date_year_range' => '-3:+3',
      '#title' => $this->t('Start date'),
      // '#default_value' => $value,
      '#required' => TRUE,
    ];

    $form['to'] = [
      '#type' => 'date',
      '#date_timezone' => 'Europe/Helsinki',
      '#date_format' => 'Y-m-d',
      // '#date_year_range' => '-3:+3',
      '#title' => $this->t('End date'),
      '#default_value' => date('Y-m-d'),
      '#required' => TRUE,
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Download'),
    ];

    // $form['#submit'][] = 'submitForm';
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $x = 1;
  }
}
