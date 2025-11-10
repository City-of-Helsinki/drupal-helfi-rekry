<?php

declare(strict_types=1);

namespace Drupal\helfi_hakuvahti\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Hakuvahti configuration entity.
 *
 * @ConfigEntityType(
 *   id = "hakuvahti_config",
 *   label = @Translation("Hakuvahti Configuration"),
 *   config_prefix = "config",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "site_id"
 *   }
 * )
 */
class HakuvahtiConfig extends ConfigEntityBase {

  /**
   * The configuration ID.
   */
  protected string $id;

  /**
   * The configuration label.
   */
  protected string $label;

  /**
   * The site ID sent to the backend server.
   */
  protected string $site_id = '';

  /**
   * Gets the site ID.
   *
   * @return string
   *   The site ID.
   */
  public function getSiteId(): string {
    return $this->site_id ?? '';
  }

  /**
   * Sets the site ID.
   *
   * @param string $site_id
   *   The site ID.
   *
   * @return $this
   */
  public function setSiteId(string $site_id): static {
    $this->site_id = $site_id;
    return $this;
  }

}
