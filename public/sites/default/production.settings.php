<?php

$config['openid_connect.client.tunnistamo']['settings']['is_production'] = TRUE;
$config['helfi_proxy.settings']['tunnistamo_return_url'] = '/fi/avoimet-tyopaikat/openid-connect/tunnistamo';
$config['helfi_google_api.settings']['indexing_api_key'] = getenv('GOOGLE_INDEXING_API_KEY');

// Send job listing -indexing requests to google when dry_run is set to false.
$config['helfi_google_api.settings']['dry_run'] = FALSE;
