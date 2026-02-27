<?php

declare(strict_types=1);

namespace Drupal\helfi_rekry_content\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\path_alias\AliasManagerInterface;

/**
 * Change helfi_rekry_content settings, e.g. set job listings 403 redirect path.
 */
class SettingsForm extends ConfigFormBase {

  use AutowireTrait;

  public function __construct(
    ConfigFactoryInterface $configFactory,
    TypedConfigManagerInterface $typedConfigManager,
    protected readonly AliasManagerInterface $aliasManager,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($configFactory, $typedConfigManager);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() : string {
    return 'helfi_rekry_content.settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() : array {
    return ['helfi_rekry_content.job_listings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $siteConfig = $this->config('helfi_rekry_content.job_listings');
    $storage = $this->entityTypeManager->getStorage('node');

    $searchPage = $siteConfig->get('search_page');
    $searchPage = $searchPage ? $storage->load($searchPage) : NULL;
    $form['job_listings']['search_page'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      '#selection_settings' => [
        'target_bundles' => ['landing_page', 'page'],
      ],
      '#title' => $this->t('Job search page'),
      '#default_value' => $searchPage,
      '#description' => $this->t('Displayed after the related jobs block, for example.'),
    ];

    $redirectPage = $siteConfig->get('redirect_404_page');
    $redirectPage = $redirectPage ? $storage->load($redirectPage) : NULL;
    $form['job_listings']['redirect_403_page'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      '#selection_settings' => [
        'target_bundles' => ['landing_page', 'page'],
      ],
      '#title' => $this->t('Closed job listing redirect page'),
      '#default_value' => $redirectPage,
      '#description' => $this->t('Page where anonymous users will be redirected from unpublished job listings'),
    ];

    $form['job_listings']['city_description_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('City description title'),
      '#default_value' => $siteConfig->get('city_description_title'),
      '#description' => $this->t('This description title will be added to all job listings.'),
      '#config_target' => 'helfi_rekry_content.job_listings:city_description_title',
    ];

    $form['job_listings']['city_description_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('City description text'),
      '#default_value' => $siteConfig->get('city_description_text'),
      '#description' => $this->t('This description text will be added to all job listings.'),
      '#config_target' => 'helfi_rekry_content.job_listings:city_description_text',
    ];

    $form['job_listings']['disable_unpublishing'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable automatic unpublishing'),
      '#description' => $this->t('When enabled, job listings missing from source data will not be unpublished. The event will still be logged.'),
      '#config_target' => 'helfi_rekry_content.job_listings:disable_unpublishing',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('helfi_rekry_content.job_listings')
      ->set('search_page', $form_state->getValue('search_page'))
      ->set('redirect_403_page', $form_state->getValue('redirect_403_page'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
