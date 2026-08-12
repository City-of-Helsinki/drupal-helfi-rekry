<?php

declare(strict_types=1);

namespace Drupal\helfi_rekry_content\Plugin\search_api\processor;

use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\helfi_rekry_content\Entity\JobListing;
use Drupal\node\NodeInterface;
use Drupal\search_api\Attribute\SearchApiProcessor;
use Drupal\search_api\Plugin\search_api\processor\EntityStatus;
use Drupal\search_api\SearchApiException;

/**
 * Excludes unpublished entities, except job listings that have been public.
 *
 * @see \Drupal\search_api\Plugin\search_api\processor\EntityStatus
 */
#[SearchApiProcessor(
  id: 'job_listing_entity_status',
  label: new TranslatableMarkup('Job listing entity status'),
  description: new TranslatableMarkup('Exclude unpublished entities from being indexed, except job listings that have been public at some point.'),
  stages: [
    'alter_items' => 0,
  ],
)]
class JobListingEntityStatus extends EntityStatus {

  /**
   * {@inheritdoc}
   *
   * @param array<string, \Drupal\search_api\Item\ItemInterface<mixed>> $items
   *   Search items being indexed, keyed by item id.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown when the processor encounters a node that is not a job listing.
   */
  public function alterIndexedItems(array &$items): void {
    foreach ($items as $item_id => $item) {
      $object = $item->getOriginalObject()->getValue();
      $enabled = TRUE;

      if ($object instanceof NodeInterface) {
        if (!$object instanceof JobListing) {
          throw new SearchApiException(sprintf('Node %s is not a job listing. This processor only supports job listing nodes.', $object->id()));
        }
        $enabled = $object->hasBeenPublic();
      }
      elseif ($object instanceof EntityPublishedInterface) {
        $enabled = $object->isPublished();
      }

      if (!$enabled) {
        unset($items[$item_id]);
      }
    }
  }

}
