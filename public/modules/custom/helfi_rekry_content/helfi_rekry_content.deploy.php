<?php

/**
 * @file
 * Contains deploy functions for HELfi Rekry Content module.
 */

/**
 * UHF-8157 Add external ids to employment and employment_type terms.
 */
function helfi_rekry_content_deploy_UHF_8157(): void {
  include_once 'helfi_rekry_content.install';
  helfi_rekry_content_ensure_taxonomy_terms();
}
