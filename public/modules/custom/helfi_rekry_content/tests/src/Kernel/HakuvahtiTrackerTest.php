<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_rekry_content\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\helfi_rekry_content\Form\SelectedFiltersCsvForm;
use Drupal\helfi_rekry_content\Service\HakuvahtiTracker;
use Drupal\KernelTests\KernelTestBase;

/**
 * Hakuvahti tracker test.
 */
class HakuvahtiTrackerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'taxonomy',
    'helfi_rekry_content',
  ];

  /**
   * {@inheritDoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->installSchema('helfi_rekry_content', ['hakuvahti_selected_filters']);
  }

  /**
   * Test saving filters.
   */
  public function testSaveAndLoadFilters(): void {
    /** @var \Drupal\helfi_rekry_content\Service\HakuvahtiTracker $tracker */
    $tracker = $this->container->get(HakuvahtiTracker::class);

    $week_ago = new \DateTime(date('Y-m-d H:i.s', strtotime('-1 week')));
    $now = new \DateTime();

    try {
      $tracker->createCsvString($week_ago, $now);
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

    $csv = $tracker->createCsvString($week_ago, $now, ';');
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

    /** @var \Drupal\helfi_rekry_content\Service\HakuvahtiTracker $tracker */
    $tracker = $this->container->get(HakuvahtiTracker::class);
    $tracker->saveSelectedFilters($filters);

    $form_state = new FormState();
    $form_state->setValue('csv_delimiter', ';');
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
    $this->assertContains($needle, explode(';', $csv));
  }

  /**
   * Test deleting old entries.
   */
  public function testDeleteOldEntries(): void {
    $tracker = $this->container->get(HakuvahtiTracker::class);
    $database = $this->container->get('database');

    $tracker->saveSelectedFilters([
      'Ammattiala' => ['Sosiaali- ja terveysala'],
      'Sijainti' => ['Helsinki'],
    ]);

    // Insert an old entry directly (4 years ago).
    $old_date = (new \DateTime())->modify('-4 years')->format('Y-m-d H:i:s');
    $database->insert('hakuvahti_selected_filters')
      ->fields([
        'token' => 'old_token_123',
        'filter_name' => 'Palvelussuhteen tyyppi',
        'filter_value' => 'Vakituinen',
        'created_at' => $old_date,
      ])
      ->execute();

    // Verify total count in DB is 3.
    $count = $database->select('hakuvahti_selected_filters')->countQuery()->execute()->fetchField();
    $this->assertEquals(3, $count, 'Database should contain 3 rows before cleanup.');

    // Run the deletion logic.
    $deleted = $tracker->deleteOldEntries();
    $this->assertEquals(1, $deleted, 'Should return 1 deleted row.');

    // Verify DB state.
    $remaining_rows = $database->select('hakuvahti_selected_filters', 't')
      ->fields('t', ['token'])
      ->execute()
      ->fetchAll();

    $this->assertCount(2, $remaining_rows, 'Database should have 2 rows remaining.');

    // Ensure the specific "old" token is gone.
    foreach ($remaining_rows as $row) {
      $this->assertNotEquals('old_token_123', $row->token, 'The old token entry should have been deleted.');
    }
  }

  /**
   * Test that deleteOldEntries returns 0 when no old entries exist.
   */
  public function testDeleteOldEntriesNoOldEntries(): void {
    $tracker = $this->container->get(HakuvahtiTracker::class);
    $database = $this->container->get('database');

    // Save recent filters only.
    $tracker->saveSelectedFilters([
      'Kieli' => ['fi'],
    ]);

    // Verify entry exists.
    $count = $database->select('hakuvahti_selected_filters')->countQuery()->execute()->fetchField();
    $this->assertEquals(1, $count, 'Database should contain 1 row.');

    // Delete should return 0 since no entries are older than 3 years.
    $deleted = $tracker->deleteOldEntries();
    $this->assertEquals(0, $deleted, 'Should return 0 deleted rows.');

    // Verify the entry still exists.
    $count = $database->select('hakuvahti_selected_filters')->countQuery()->execute()->fetchField();
    $this->assertEquals(1, $count, 'Database should still contain 1 row.');
  }

}
