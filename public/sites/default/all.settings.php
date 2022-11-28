<?php

/**
 * @file
 * Contains site specific overrides.
 */

// Client ID for Helbit integration.
$config['helfi_rekry_content.settings']['helbit_client_id'] = getenv('HELBIT_CLIENT_ID');

// Elastic proxy URL.
$config['elastic_proxy.settings']['elastic_proxy_url'] = getenv('ELASTIC_PROXY_URL');
