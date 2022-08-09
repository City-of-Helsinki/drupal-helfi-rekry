<?php

declare(strict_types = 1);

namespace Drupal\helfi_rekry_content\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Path\PathValidatorInterface;
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

    $form['job_listings']['redirect_403'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Unpublished job listing redirect page for anonymous users'),
      '#default_value' => $siteConfig->get('redirect_403'),
      '#size' => 40,
      '#description' => $this->t('This page is displayed for anonymous users when a job listing is unpublished. Redirect to page that contains information about old and removed job listings.'),
    ];

    $language = \Drupal::languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();

    $form['job_listings_']['city_description_' . $language] = [
      '#type' => 'textarea',
      '#title' => $this->t('City description'),
      '#default_value' => $siteConfig->get('city_description_' . $language),
      '#description' => $this->t('This description will be added to all job listings.'),
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
    $language = \Drupal::languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();

    $this->config('helfi_rekry_content.job_listings')
      ->set('redirect_403', $form_state->getValue('redirect_403'))
      ->set('city_description_' . $language, $form_state->getValue('city_description_' . $language))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
