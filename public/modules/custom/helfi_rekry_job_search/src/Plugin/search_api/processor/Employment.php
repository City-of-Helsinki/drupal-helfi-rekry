<?php

declare(strict_types = 1);

namespace Drupal\helfi_rekry_job_search\Plugin\search_api\processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;

/**
 * Generates a list of valid employment filter values.
 *
 * @SearchApiProcessor(
 *    id = "employment",
 *    label = @Translation("Employment"),
 *    description = @Translation("Generates a list of values for employment filter"),
 *    stages = {
 *      "add_properties" = 0
 *    },
 *    locked = true,
 *    hidden = true
 * )
 */
class Employment extends ProcessorPluginBase {

  const VALID_EMPLOYMENTS = [
    6, 7, 10,
  ];

  const VALID_EMPLOYMENT_TYPES = [
    1, 3, 5,
  ];

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DataSourceInterface $datasource = NULL) {
    $properties = [];

    if ($datasource) {
      $definition = [
        'label' => $this->t('Employment'),
        'description' => $this->t('Generates a list of valid employment filter values'),
        'type' => 'nested',
        'processor_id' => $this->getPluginId(),
        'hidden' => FALSE,
      ];
      $properties['employment'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $datasourceId = $item->getDataSourceId();
    $employments;
    $employment_types;
    $values = [];

    if ($datasourceId === 'entity:node' && $node = $item->getOriginalObject()->getValue()) {
      $employments = $this->getValidValues($node->get('field_employment')->referencedEntities(), self::VALID_EMPLOYMENTS);
      $employment_types = $this->getValidValues($node->get('field_employment_type')->referencedEntities(), self::VALID_EMPLOYMENT_TYPES);
    }

    if ($employments) {
      $values = array_merge($values, $employments);
    }

    if ($employment_types) {
      $values = array_merge($values, $employment_types);
    }

    $fields = $this->getFieldsHelper()->filterForPropertyPath($item->getFields(), 'entity:node', 'employment');
    if (isset($fields['employment'])) {
      $fields['employment']->setValues($values);
    }
  }

  /**
   * Return a list of accepted values (if present).
   *
   * @param \Drupal\taxonomy\TermInterface[] $terms
   *   Array of values attached to the node.
   * @param array $valid_values
   *   Array of valid values to check against.
   *
   * @return array
   *   Array of found valid values.
   */
  public function getValidValues(array $terms, array $valid_values): array {
    $found = [];

    if (!is_array($terms) || !count($terms)) {
      return $found;
    }

    foreach ($terms as $term) {
      $tid = $term->id();
      if (in_array($tid, $valid_values)) {
        $found[] = [
          'id' => $tid,
          'name' => $term->getName(),
        ];
      }
    }

    return $found;
  }

}
