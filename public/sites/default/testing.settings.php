<?php

// Enable helbit test environment if HELBIT_TEST_CLIENT_ID is set.
if (getenv('HELBIT_TEST_CLIENT_ID')) {
  $config['helfi_rekry_content.settings']['helbit_clients'][] = [
    'client_id' => getenv('HELBIT_TEST_CLIENT_ID'),
    'base_url' => 'https://testhelbit.fi',
  ];
}
