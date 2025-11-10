<?php

declare(strict_types=1);

namespace Drupal\helfi_hakuvahti;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Hakuvahti configurations.
 */
class HakuvahtiConfigListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['label'] = $this->t('Label');
    $header['id'] = $this->t('Machine name');
    $header['site_id'] = $this->t('Site ID');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\helfi_hakuvahti\Entity\HakuvahtiConfig $entity */
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['site_id'] = $entity->getSiteId();
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render(): array {
    $build = parent::render();
    $build['table']['#empty'] = $this->t('No hakuvahti configurations available. <a href=":link">Add a configuration</a>.', [
      ':link' => $this->entityType->getLinkTemplate('add-form'),
    ]);
    return $build;
  }

}
