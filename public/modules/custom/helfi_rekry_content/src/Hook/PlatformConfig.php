<?php

declare(strict_types=1);

namespace Drupal\helfi_rekry_content\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Platform config hooks.
 */
final readonly class PlatformConfig {

  /**
   * Implements hook_platform_config_grant_permissions().
   *
   * @codeCoverageIgnore
   */
  #[Hook('platform_config_grant_permissions')]
  public function permissions(): array {
    return [
      'admin' => [
        'edit any job_listing content',
        'edit any job_listing_image media',
        'edit own job_listing content',
        'edit own job_listing_image media',
        'revert job_listing revisions',
        'view job_listing revisions',
      ],
      'rekry_admin' => [
        'delete any job_listing content',
        'delete job_listing revisions',
        'delete own job_listing content',
        'edit any job_listing content',
        'edit any job_listing_image media',
        'edit own job_listing content',
        'edit own job_listing_image media',
        'revert job_listing revisions',
        'set job_listing published on date',
        'translate job_listing node',
        'view job_listing published on date',
        'view job_listing revisions',
      ],
      'content_producer' => [
        'edit own job_listing content',
        'revert job_listing revisions',
        'view job_listing revisions',
      ],
      'editor' => [
        'edit any job_listing content',
        'edit any job_listing_image media',
        'edit own job_listing content',
        'edit own job_listing_image media',
        'revert job_listing revisions',
        'view job_listing revisions',
      ],
      'hr' => [
        'access content overview',
        'access user profiles',
        'administer nodes',
        'cancel account',
        'change own username',
        'delete own files',
        'disable own tfa',
        'edit any job_listing content',
        'rebuild node access permissions',
        'schedule publishing of nodes',
        'setup own tfa',
        'use text format full_html',
        'use text format minimal',
        'view any unpublished job_listing content',
        'view own unpublished content',
        'view scheduled content',
        'view the administration theme',
      ],
      'read_only' => [
        'view any unpublished job_listing content',
      ],
    ];
  }

}
