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
 *   label_collection = @Translation("Hakuvahti Configurations"),
 *   label_singular = @Translation("hakuvahti configuration"),
 *   label_plural = @Translation("hakuvahti configurations"),
 *   label_count = @PluralTranslation(
 *     singular = "@count hakuvahti configuration",
 *     plural = "@count hakuvahti configurations",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\helfi_hakuvahti\HakuvahtiConfigListBuilder",
 *     "form" = {
 *       "add" = "Drupal\helfi_hakuvahti\Form\HakuvahtiConfigForm",
 *       "edit" = "Drupal\helfi_hakuvahti\Form\HakuvahtiConfigForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     }
 *   },
 *   config_prefix = "config",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "site_id"
 *   },
 *   links = {
 *     "collection" = "/admin/config/services/hakuvahti",
 *     "add-form" = "/admin/config/services/hakuvahti/add",
 *     "edit-form" = "/admin/config/services/hakuvahti/{hakuvahti_config}/edit",
 *     "delete-form" = "/admin/config/services/hakuvahti/{hakuvahti_config}/delete"
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
