<?php

$config['openid_connect.client.tunnistamo']['settings']['is_production'] = TRUE;
$config['helfi_proxy.settings']['tunnistamo_return_url'] = '/fi/avoimet-tyopaikat/openid-connect/tunnistamo';
$config['helfi_google_api.settings']['indexing_api_key'] = getenv('GOOGLE_INDEXING_API_KEY');
$config['helfi_google_api.settings']['enabled'] = getenv('APP_ENV') === 'production';
