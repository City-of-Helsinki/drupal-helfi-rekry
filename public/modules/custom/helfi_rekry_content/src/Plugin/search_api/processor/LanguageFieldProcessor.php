<?php

declare(strict_types=1);

namespace Drupal\helfi_rekry_content\Plugin\search_api\processor;

use Drupal\Core\Entity\EntityInterface;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;

/**
 * Adds `_language` to the index.
 *
 * This processor adds _language field to the search api index. The field was
 * renamed to `search_api_language` in elasticsearch_connector v8 update. We
 * cannot modify rekry search api index without breaking existing hakuvahti
 * queries.
 *
 * @SearchApiProcessor(
 *   id = "language_field",
 *   label = @Translation("Language field"),
 *   description = @Translation("Add _language field to the index."),
 *   stages = {
 *     "add_properties" = 20,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class LanguageFieldProcessor extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(?DatasourceInterface $datasource = NULL): array {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Language'),
        'description' => $this->t('The legacy _language field.'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
      ];
      $properties['_language'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item): void {
    $object = $item->getOriginalObject()->getValue();

    if ($object instanceof EntityInterface) {
      $indexableValue = $object->language()->getId();

      $itemFields = $item->getFields();
      $itemFields = $this->getFieldsHelper()
        ->filterForPropertyPath($itemFields, NULL, '_language');

      foreach ($itemFields as $itemField) {
        $itemField->addValue($indexableValue);
      }

    }
  }

}
