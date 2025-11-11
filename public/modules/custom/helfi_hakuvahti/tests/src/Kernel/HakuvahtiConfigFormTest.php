<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_hakuvahti\Kernel;

use Drupal\helfi_hakuvahti\Entity\HakuvahtiConfig;
use Drupal\helfi_hakuvahti\Form\HakuvahtiConfigForm;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests for HakuvahtiConfigForm.
 *
 * @group helfi_hakuvahti
 */
class HakuvahtiConfigFormTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'helfi_hakuvahti',
    'user',
  ];

  /**
   * The form object.
   *
   * @var \Drupal\helfi_hakuvahti\Form\HakuvahtiConfigForm
   */
  protected HakuvahtiConfigForm $form;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['helfi_hakuvahti']);
    $this->installEntitySchema('hakuvahti_config');

    // Create form instance with proper dependency injection.
    $this->form = HakuvahtiConfigForm::create($this->container);
    $this->form->setModuleHandler($this->container->get('module_handler'));
    $this->form->setEntityTypeManager($this->container->get('entity_type.manager'));
  }

  /**
   * Tests form structure for new config.
   */
  public function testFormStructureForNewConfig(): void {
    $entity = HakuvahtiConfig::create([]);
    $this->form->setEntity($entity);

    $form_state = $this->createMock('\Drupal\Core\Form\FormStateInterface');
    $form = $this->form->buildForm([], $form_state);

    // Verify form has required fields.
    $this->assertArrayHasKey('label', $form);
    $this->assertArrayHasKey('id', $form);
    $this->assertArrayHasKey('site_id', $form);

    // Verify id field is not disabled for new entities.
    $this->assertArrayHasKey('#disabled', $form['id']);
    $this->assertFalse($form['id']['#disabled']);
  }

  /**
   * Tests form structure for existing config (edit mode).
   */
  public function testFormStructureForExistingConfig(): void {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('hakuvahti_config');

    /** @var \Drupal\helfi_hakuvahti\Entity\HakuvahtiConfig $entity */
    $entity = $storage->create([
      'id' => 'test',
      'label' => 'Test Config',
      'site_id' => 'test-site',
    ]);
    $entity->save();

    $this->form->setEntity($entity);
    $form_state = $this->createMock('\Drupal\Core\Form\FormStateInterface');
    $form = $this->form->buildForm([], $form_state);

    // Machine name should be disabled for existing entities.
    $this->assertArrayHasKey('#disabled', $form['id']);
    $this->assertTrue($form['id']['#disabled']);
  }

  /**
   * Tests saving a new config via form.
   */
  public function testSaveNewConfig(): void {
    $entity = HakuvahtiConfig::create([]);

    $form_state = $this->createMock('\Drupal\Core\Form\FormStateInterface');
    $form_state->expects($this->any())
      ->method('getValue')
      ->willReturnMap([
        ['label', 'New Config'],
        ['id', 'new_config'],
        ['site_id', 'new-site-id'],
      ]);

    // Set entity on form.
    $this->form->setEntity($entity);
    $this->form->buildForm([], $form_state);

    // Set values on entity.
    $entity->set('label', 'New Config');
    $entity->set('id', 'new_config');
    $entity->set('site_id', 'new-site-id');

    // Save the entity.
    $this->form->save([], $form_state);

    // Verify entity was saved.
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('hakuvahti_config');

    /** @var \Drupal\helfi_hakuvahti\Entity\HakuvahtiConfig|null $saved */
    $saved = $storage->load('new_config');

    $this->assertNotNull($saved);
    $this->assertEquals('New Config', $saved->label());
    $this->assertEquals('new-site-id', $saved->getSiteId());
  }

  /**
   * Tests updating an existing config via form.
   */
  public function testUpdateExistingConfig(): void {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('hakuvahti_config');

    /** @var \Drupal\helfi_hakuvahti\Entity\HakuvahtiConfig $entity */
    $entity = $storage->create([
      'id' => 'test',
      'label' => 'Original Label',
      'site_id' => 'original-site',
    ]);
    $entity->save();

    // Update the entity.
    $entity->set('label', 'Updated Label');
    $entity->set('site_id', 'updated-site');

    $form_state = $this->createMock('\Drupal\Core\Form\FormStateInterface');

    // Build form and save.
    $this->form->setEntity($entity);
    $this->form->buildForm([], $form_state);
    $this->form->save([], $form_state);

    // Reload and verify.
    /** @var \Drupal\helfi_hakuvahti\Entity\HakuvahtiConfig|null $updated */
    $updated = $storage->load('test');

    $this->assertNotNull($updated);
    $this->assertEquals('Updated Label', $updated->label());
    $this->assertEquals('updated-site', $updated->getSiteId());
  }

  /**
   * Tests form entity method.
   */
  public function testFormEntityMethod(): void {
    $entity = HakuvahtiConfig::create([
      'id' => 'test',
      'label' => 'Test',
      'site_id' => 'test-site',
    ]);

    $form_state = $this->createMock('\Drupal\Core\Form\FormStateInterface');
    $this->form->setEntity($entity);
    $this->form->buildForm([], $form_state);

    // Verify entity() method returns the entity.
    $this->assertSame($entity, $this->form->getEntity());
  }

  /**
   * Tests machine name generation from label.
   */
  public function testMachineNameGeneration(): void {
    $entity = HakuvahtiConfig::create([]);
    $this->form->setEntity($entity);
    $form_state = $this->createMock('\Drupal\Core\Form\FormStateInterface');
    $form = $this->form->buildForm([], $form_state);

    // Verify machine name field exists and has machine_name configuration.
    $this->assertArrayHasKey('id', $form);
    $this->assertArrayHasKey('#machine_name', $form['id']);
  }

  /**
   * Tests that machine name cannot be changed for existing entities.
   */
  public function testMachineNameImmutableForExistingEntity(): void {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('hakuvahti_config');

    /** @var \Drupal\helfi_hakuvahti\Entity\HakuvahtiConfig $entity */
    $entity = $storage->create([
      'id' => 'immutable',
      'label' => 'Immutable Test',
      'site_id' => 'test-site',
    ]);
    $entity->save();

    $this->form->setEntity($entity);
    $form_state = $this->createMock('\Drupal\Core\Form\FormStateInterface');
    $form = $this->form->buildForm([], $form_state);

    // Machine name should be disabled.
    $this->assertArrayHasKey('#disabled', $form['id']);
    $this->assertTrue($form['id']['#disabled']);

    // Verify the entity still exists with original ID.
    /** @var \Drupal\helfi_hakuvahti\Entity\HakuvahtiConfig|null $reloaded */
    $reloaded = $storage->load('immutable');
    $this->assertNotNull($reloaded);
    $this->assertEquals('immutable', $reloaded->id());
  }

  /**
   * Tests form with empty site_id.
   */
  public function testFormWithEmptySiteId(): void {
    $entity = HakuvahtiConfig::create([
      'id' => 'empty_site',
      'label' => 'Empty Site Test',
      'site_id' => '',
    ]);

    $this->form->setEntity($entity);
    $form_state = $this->createMock('\Drupal\Core\Form\FormStateInterface');
    $form = $this->form->buildForm([], $form_state);

    // Site ID field should exist.
    $this->assertArrayHasKey('site_id', $form);
  }

}
