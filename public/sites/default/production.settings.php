<?php

$config['openid_connect.client.tunnistamo']['settings']['is_production'] = TRUE;
$config['helfi_proxy.settings']['tunnistamo_return_url'] = '/fi/avoimet-tyopaikat/openid-connect/tunnistamo';
$config['helfi_google_api.settings']['indexing_api_key'] = getenv('GOOGLE_INDEXING_API_KEY');

// Remove the comment when it's time to enable the feature on production
// $config['helfi_google_api.settings']['dry_run'] = FALSE;
