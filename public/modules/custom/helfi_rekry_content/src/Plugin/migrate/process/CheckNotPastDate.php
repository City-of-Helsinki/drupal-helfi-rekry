<?php

declare(strict_types = 1);

namespace Drupal\helfi_rekry_content\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Check that date string does not represent a past date.
 *
 * @MigrateProcessPlugin(
 *   id = "not_past_date"
 * )
 *
 * @code
 * publish_on:
 *   plugin: not_past_date
 *   source: publication_starts
 * @endcode
 */
class CheckNotPastDate extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\migrate\MigrateSkipProcessException
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) : int {
    $valueTimestamp = strtotime($value);
    if ($valueTimestamp <= \Drupal::time()->getCurrentTime()) {
      throw new MigrateSkipProcessException("The date must be in the future.");
    }
    return $valueTimestamp;
  }

}
