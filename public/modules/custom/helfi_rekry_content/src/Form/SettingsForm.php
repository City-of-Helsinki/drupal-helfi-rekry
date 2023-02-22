<?php

declare(strict_types = 1);

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
  public static function create(ContainerInterface $container) {
    return new static(
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

    $search_page_node = NULL;
    if ($siteConfig->get('search_page')) {
      $search_page_node = Node::load($siteConfig->get('search_page'));
    }
    $form['job_listings']['search_page'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      '#selection_settings' => [
        'target_bundles' => ['landing_page', 'page'],
      ],
      '#title' => $this->t('Job search page'),
      '#default_value' => $search_page_node,
      '#description' => $this->t('Displayed after the related jobs block, for example.'),
    ];

    $form['job_listings']['redirect_403'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Unpublished job listing redirect page for anonymous users'),
      '#default_value' => $siteConfig->get('redirect_403'),
      '#size' => 40,
      '#description' => $this->t('This page is displayed for anonymous users when a job listing is unpublished. Redirect to page that contains information about old and removed job listings.'),
    ];

    $form['job_listings_']['city_description_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('City description title'),
      '#default_value' => $siteConfig->get('city_description_title'),
      '#description' => $this->t('This description title will be added to all job listings.'),
    ];

    $form['job_listings_']['city_description_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('City description text'),
      '#default_value' => $siteConfig->get('city_description_text'),
      '#description' => $this->t('This description text will be added to all job listings.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->isValueEmpty('redirect_403')) {
      $form_state->setValueForElement($form['job_listings']['redirect_403'], $this->aliasManager->getPathByAlias($form_state->getValue('redirect_403')));
    }
    if (($value = $form_state->getValue('redirect_403')) && $value[0] !== '/') {
      $form_state->setErrorByName('redirect_403',
        $this->t("The path '%path' has to start with a slash.", ['%path' => $form_state->getValue('redirect_403')])
      );
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('helfi_rekry_content.job_listings')
      ->set('search_page', $form_state->getValue('search_page'))
      ->set('redirect_403', $form_state->getValue('redirect_403'))
      ->set('city_description_title', $form_state->getValue('city_description_title'))
      ->set('city_description_text', $form_state->getValue('city_description_text'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
