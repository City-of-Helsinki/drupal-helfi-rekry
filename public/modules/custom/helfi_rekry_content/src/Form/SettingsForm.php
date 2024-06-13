<?php

declare(strict_types=1);

namespace Drupal\helfi_rekry_content\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\node\Entity\Node;
use Drupal\path_alias\AliasManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Change helfi_rekry_content settings, e.g. set job listings 403 redirect path.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The path alias manager.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected AliasManagerInterface $aliasManager;

  /**
   * The path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected PathValidatorInterface $pathValidator;

  /**
   * Constructs a SettingsForm object for helfi_rekry_content.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\path_alias\AliasManagerInterface $alias_manager
   *   The path alias manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AliasManagerInterface $alias_manager) {
    parent::__construct($config_factory);
    $this->aliasManager = $alias_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) : self {
    return new self(
      $container->get('config.factory'),
      $container->get('path_alias.manager'),
    );
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

    // phpcs:ignore
    $searchPage = Node::load($siteConfig->get('search_page'));
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

    // phpcs:ignore
    $redirectPage = Node::load($siteConfig->get('redirect_403_page'));
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
    ];

    $form['job_listings']['city_description_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('City description text'),
      '#default_value' => $siteConfig->get('city_description_text'),
      '#description' => $this->t('This description text will be added to all job listings.'),
    ];

    $form['job_listings']['hakuvahti_tos_checkbox_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Hakuvahti TOS Checkbox Title'),
      '#default_value' => $siteConfig->get('hakuvahti_tos_checkbox_title'),
      '#description' => $this->t('Title for the TOS checkbox title.'),
    ];    

    $form['job_listings']['hakuvahti_tos_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Hakuvahti TOS Title'),
      '#default_value' => $siteConfig->get('hakuvahti_tos_title'),
      '#description' => $this->t('Title for the link to the Terms of Service of Hakuvahti.'),
    ];

    $form['job_listings']['hakuvahti_tos_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Hakuvahti TOS URL'),
      '#default_value' => $siteConfig->get('hakuvahti_tos_url'),
      '#description' => $this->t('URL for the link to the Terms of Service of Hakuvahti.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('helfi_rekry_content.job_listings')
      ->set('search_page', $form_state->getValue('search_page'))
      ->set('redirect_403_page', $form_state->getValue('redirect_403_page'))
      ->set('city_description_title', $form_state->getValue('city_description_title'))
      ->set('city_description_text', $form_state->getValue('city_description_text'))
      ->set('hakuvahti_tos_checkbox_title', $form_state->getValue('hakuvahti_tos_checkbox_title'))
      ->set('hakuvahti_tos_title', $form_state->getValue('hakuvahti_tos_title'))
      ->set('hakuvahti_tos_url', $form_state->getValue('hakuvahti_tos_url'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
