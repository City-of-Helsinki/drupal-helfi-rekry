<?php

declare(strict_types=1);

namespace Drupal\helfi_hakuvahti\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for Hakuvahti configuration add and edit forms.
 */
class HakuvahtiConfigForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\helfi_hakuvahti\Entity\HakuvahtiConfig $config */
    $config = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $config->label(),
      '#description' => $this->t('A human-readable name for this configuration.'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $config->id(),
      '#machine_name' => [
        'exists' => '\Drupal\helfi_hakuvahti\Entity\HakuvahtiConfig::load',
      ],
      '#disabled' => !$config->isNew(),
      '#description' => $this->t('A unique machine-readable name. Used in the URL parameter (e.g., ?config=jobs).'),
    ];

    $form['site_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Site ID'),
      '#maxlength' => 255,
      '#default_value' => $config->getSiteId() ?? '',
      '#description' => $this->t('The site ID that will be sent to the Hakuvahti backend server.'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    /** @var \Drupal\helfi_hakuvahti\Entity\HakuvahtiConfig $config */
    $config = $this->entity;
    $status = $config->save();

    $message_args = ['%label' => $config->label()];
    $message = $status === SAVED_NEW
      ? $this->t('Created new hakuvahti configuration %label.', $message_args)
      : $this->t('Updated hakuvahti configuration %label.', $message_args);

    $this->messenger()->addStatus($message);
    $form_state->setRedirectUrl($config->toUrl('collection'));

    return $status;
  }

}
