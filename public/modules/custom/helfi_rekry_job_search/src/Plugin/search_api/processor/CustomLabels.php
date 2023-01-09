<?php

declare(strict_types = 1);

namespace Drupal\helfi_rekry_job_search\Plugin\search_api\processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;

/**
 * Adds special labels to job listings.
 *
 * @SearchApiProcessor(
 *   id = "helfi_rekry_custom_labels",
 *   label = @Translation("Custom labels"),
 *   description = @Translation("Marks documents as summer jobs, temps job, etc."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class CustomLabels extends ProcessorPluginBase {

  // Hadcoded tids for different custom labels.
  const SUMMER_JOBS = '1160';
  const YOUTH_SUMMER_JOBS = '1161';
  const COOL_SUMMER = '1162';
  const CONTINUOUS = '1163';
  const INTERNSHIP = '10';

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Custom labels'),
        'description' => $this->t('Marks documents as summer jobs, temps job, etc.'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
      ];
      $properties['custom_labels'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $datasourceId = $item->getDataSourceId();
    $node = $item->getOriginalObject()->getValue();
    if (!$datasourceId === 'entity:node' || !$node) {
      return;
    }

    $customLabel = NULL;
    $terms = $node->get('field_employment')->getValue();

    if (count($terms) > 0) {
      $tid = $terms[0]['target_id'];
      $customLabel = match ($tid) {
        self::SUMMER_JOBS => 'is_summer_job',
        self::YOUTH_SUMMER_JOBS => 'is_youth_summer_job',
        self::INTERNSHIP => 'is_internship',
        self::COOL_SUMMER, self::YOUTH_SUMMER_JOBS => 'is_youth_summer_job',
        default => NULL
      };
    }

    if ($customLabel) {
      $fields = $item->getFields();
      $fields = $this->getFieldsHelper()
        ->filterForPropertyPath($fields, NULL, 'custom_labels');
      foreach ($fields as $field) {
        $field->addValue($customLabel);
      }
    }
  }

}
