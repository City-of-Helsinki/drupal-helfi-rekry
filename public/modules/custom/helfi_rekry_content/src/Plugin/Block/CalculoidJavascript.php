<?php

namespace Drupal\helfi_rekry_content\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'CalculoidJavascript' Block.
 *
 * @Block(
 *   id = "calculoid_javascript_block",
 *   admin_label = @Translation("Calculoid Javascript"),
 *   category = @Translation("Calculoid Javascript"),
 * )
 */
class CalculoidJavascript extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#theme' => 'calculoid_javascript_block',
    ];
  }

}
