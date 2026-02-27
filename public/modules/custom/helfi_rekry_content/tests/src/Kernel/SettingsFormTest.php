<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_rekry_content\Kernel;

use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormState;
use Drupal\helfi_rekry_content\Form\SettingsForm;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the SettingsForm.
 */
#[RunTestsInSeparateProcesses]
#[Group('helfi_rekry_content')]
class SettingsFormTest extends RekryKernelTestBase {

  use NodeCreationTrait;
  use ContentTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'node',
    'field',
    'user',
    'text',
    'filter',
    'path_alias',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('path_alias');
    $this->installSchema('node', 'node_access');
    $this->installConfig(['node', 'filter']);

    $this
      ->config('helfi_rekry_content.job_listings')
      ->set('langcode', 'en')
      ->save();
  }

  /**
   * Tests that submitting the form saves config values.
   */
  public function testSettingsFormSubmit(): void {
    $this->createContentType(['type' => 'page']);

    $searchPage = $this->createNode(['type' => 'page', 'title' => 'Search page']);
    $redirectPage = $this->createNode(['type' => 'page', 'title' => 'Redirect page']);

    $formState = new FormState();
    $formState->setValues([
      'search_page' => sprintf('%s (%s)', $searchPage->label(), $searchPage->id()),
      'redirect_403_page' => sprintf('%s (%s)', $redirectPage->label(), $redirectPage->id()),
      'city_description_title' => 'Helsinki city',
      'city_description_text' => 'A great place to work.',
      'disable_unpublishing' => TRUE,
    ]);

    $formBuilder = $this->container->get(FormBuilderInterface::class);
    $formBuilder->submitForm(SettingsForm::class, $formState);

    $this->assertEmpty($formState->getErrors());

    $config = $this->config('helfi_rekry_content.job_listings');
    $this->assertEquals($searchPage->id(), $config->get('search_page'));
    $this->assertEquals($redirectPage->id(), $config->get('redirect_403_page'));
    $this->assertEquals('Helsinki city', $config->get('city_description_title'));
    $this->assertEquals('A great place to work.', $config->get('city_description_text'));
    $this->assertTrue($config->get('disable_unpublishing'));
  }

}
