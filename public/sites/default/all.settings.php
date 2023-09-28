<?php

/**
 * @file
 * Contains site specific overrides.
 */

// Client ID for Helbit integration.
$config['helfi_rekry_content.settings']['helbit_client_id'] = getenv('HELBIT_CLIENT_ID');

// Elasticsearch settings.
if (getenv('ELASTICSEARCH_URL')) {
  $config['elasticsearch_connector.cluster.rekry']['url'] = getenv('ELASTICSEARCH_URL');

  if (getenv('ELASTIC_USER') && getenv('ELASTIC_PASSWORD')) {
    $config['elasticsearch_connector.cluster.rekry']['options']['use_authentication'] = '1';
    $config['elasticsearch_connector.cluster.rekry']['options']['authentication_type'] = 'Basic';
    $config['elasticsearch_connector.cluster.rekry']['options']['username'] = getenv('ELASTIC_USER');
    $config['elasticsearch_connector.cluster.rekry']['options']['password'] = getenv('ELASTIC_PASSWORD');
  }
}

// Elastic proxy URL.
$config['elastic_proxy.settings']['elastic_proxy_url'] = getenv('ELASTIC_PROXY_URL');

// Sentry DSN for React.
$config['react_search.settings']['sentry_dsn_react'] = getenv('SENTRY_DSN_REACT');
