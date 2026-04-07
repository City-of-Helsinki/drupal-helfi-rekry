<?php

declare(strict_types=1);

namespace Drupal\helfi_rekry_content\Hook;

use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Platform config hooks.
 */
final class JobListingNodeHook {

  use StringTranslationTrait;

  public function __construct(
    private MessengerInterface $messenger,
    private AccountProxyInterface $currentUser,
  ) {
  }

  /**
   * Implements hook_form_BASE_FORM_ID_alter().
   */
  #[Hook('form_node_job_listing_edit_form_alter')]
  public function jobListingNodeFormAlter(
    array &$form,
    FormStateInterface $form_state,
  ): void {
    $formObject = $form_state->getFormObject();
    assert($formObject instanceof EntityFormInterface);
    $editAccess = $formObject
      ->getEntity()
      ->access('edit', $this->currentUser);

    // If the user is not permitted to edit entity, do nothing.
    if ($editAccess) {
      return;
    }

    // If the user has a permission to edit the disabled fields,
    // show them a warning message.
    if (in_array('rekry_admin', $this->currentUser->getRoles())) {
      $this->messenger->addWarning($this->t('This content is automatically imported from the API. Manual changes may be overwritten during the import.'));
      return;
    }

    // A list of fields which are imported from Helbit.
    // The editor should not edit these fields.
    $disabledFields = [
      'title',
      'field_salary_class',
      'field_contacts',
      'field_recruitment_id',
      'field_last_changed_remote',
      'field_recruitment_type',
      'field_task_area',
      'field_publication_ends',
      'field_publication_starts',
      'field_link_to_presentation',
      'field_employment_type',
      'job_description',
      'field_organization_description',
      'field_organization',
      'field_jobs',
      'field_organization_name',
      'field_salary',
      'field_job_duration',
      'field_address',
      'field_postal_code',
      'field_postal_area',
      'field_link_to_application',
      'field_employment',
      'field_image',
      'field_video',
      'field_original_language',
      'field_anonymous',
    ];

    foreach ($disabledFields as $field) {
      $form[$field]['#disabled'] = TRUE;
    }
  }

}
