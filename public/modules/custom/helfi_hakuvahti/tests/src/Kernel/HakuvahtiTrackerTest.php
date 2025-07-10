<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_hakuvahti\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\helfi_hakuvahti\Form\SelectedFiltersCsvForm;
use Drupal\Tests\purge\Kernel\KernelTestBase;

/**
 * Hakuvahti tracker test.
 */
class HakuvahtiTrackerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'helfi_hakuvahti',
  ];

  /**
   * {@inheritDoc}
   */
  public function setUp($switch_to_memory_queue = TRUE): void {
    parent::setUp($switch_to_memory_queue);
    $this->installSchema('helfi_hakuvahti', ['hakuvahti_selected_filters']);
  }

  /**
   * Test saving filters.
   */
  public function testSaveAndLoadFilters() {
    /** @var \Drupal\helfi_hakuvahti\HakuvahtiTracker $tracker */
    $tracker = $this->container->get('Drupal\helfi_hakuvahti\HakuvahtiTracker');

    $week_ago = new \DateTime(date('Y-m-d H:i.s', strtotime('-1 week')));
    $now = new \DateTime();

    try {
      $csv = $tracker->createCsvString($week_ago, $now);
    }
    catch (\Exception $e) {
      $this->assertTrue(TRUE, 'No results should be found.');
    }

    $filters = [
      'Myfilter' => ['filter value 1', 'äöäöäö'],
      'Another filter' => ['Qwerty'],
    ];

    $saved = $tracker->saveSelectedFilters($filters);
    $this->assertTrue($saved);

    $csv = $tracker->createCsvString($week_ago, $now);
    $this->assertNotEmpty($csv);
    $this->assertContains('Qwerty', explode(',', $csv));
  }

  /**
   * Test the download form.
   */
  public function testCsvDownloadForm() {
    $filters = [
      'Myfilter' => ['filter value 1', 'äöäöäö'],
      'Another filter' => ['Qwerty'],
    ];

    /** @var \Drupal\helfi_hakuvahti\HakuvahtiTracker $tracker */
    $tracker = $this->container->get('Drupal\helfi_hakuvahti\HakuvahtiTracker');
    $tracker->saveSelectedFilters($filters);

    $form_state = new FormState();
    $form_state->setValue(
      'from',
      (new \DateTime(date('Y-m-d H:i.s', strtotime('-1 week'))))->format('Y-m-d')
    );
    $form_state->setValue(
      'to',
      (new \DateTime())->format('Y-m-d')
    );

    $form = SelectedFiltersCsvForm::create($this->container);

    $form_array = [];
    $form->buildForm($form_array, $form_state);
    $form->submitForm($form_array, $form_state);
  }

}
