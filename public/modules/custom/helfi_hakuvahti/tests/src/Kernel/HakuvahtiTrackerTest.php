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
  public function testSaveAndLoadFilters(): void {
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
    $this->assertCsv($csv, 'Qwerty');
  }

  /**
   * Test the download form.
   */
  public function testCsvDownloadForm(): void {
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

    $id = $form->getFormId();
    $this->assertEquals('helfi_hakuvahti_csv_download_form', $id);

    $form_array = [];
    $form->buildForm($form_array, $form_state);
    $form->submitForm($form_array, $form_state);
    $response = $form_state->getResponse();

    $this->assertEquals('text/csv; charset=UTF-8', $response->headers->get('Content-Type'));
    $this->assertCsv($response->getContent(), 'Qwerty');
  }

  /**
   * Assert csv content.
   *
   * @param string $csv
   *   The csv string.
   * @param string $needle
   *   The words to look for.
   */
  private function assertCsv(string $csv, string $needle): void {
    $this->assertContains($needle, explode(',', $csv));
  }

}
