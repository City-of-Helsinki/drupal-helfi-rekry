<?php

declare(strict_types=1);

namespace Drupal\helfi_hakuvahti;

use Drupal\Core\Database\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Save hakuvahti filter selections to database.
 */
readonly class HakuvahtiTracker {

  /**
   * The constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The connection.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */
  public function __construct(
    private readonly Connection $connection,
    #[Autowire(service: 'logger.channel.helfi_hakuvahti')]
    private readonly LoggerInterface $logger,
  ) {
  }

  /**
   * Save the selected hakuvahti filter selections.
   *
   * @param array $task_areas
   *   The selected task areas.
   * @param array $employment_type_labels
   *   The selected employment types.
   * @param array $area_filter_labels
   *   The selected areas.
   * @param string $language
   *   The selected language.
   */
  public function saveSelectedFilters(
    array $task_areas,
    array $employment_type_labels,
    array $area_filter_labels,
    string $language,
  ) {
    $now = floor(microtime(TRUE) * 1000);
    $query = $this->connection
      ->insert('hakuvahti_selected_filters')
      ->fields(['filter_name', 'filter_value', 'saved_at']);

    $dropdown_filters = [
      'Ammattiala' => $task_areas,
      'Palvelussuhteen tyyppi' => $employment_type_labels,
      'Sijainti' => $area_filter_labels,
      'Kieli' => [$language],
    ];

    foreach ($dropdown_filters as $filter_name => $values) {
      foreach ($values as $value) {
        $query->values([
          'filter_name' => $filter_name,
          'filter_value' => $value,
          'saved_at' => $now,
        ]);
      }
    }

    try {
      $query->execute();
    }
    catch (\Exception $e) {
      $this->logger->error("Unable to save hakuvahti filters: {$e->getMessage()}");
    }
  }

}
