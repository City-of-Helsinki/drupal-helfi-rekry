<?php

$config['helfi_proxy.settings']['asset_path'] = 'rekry-assets';

$config['helfi_proxy.settings']['prefixes'] = [
  'en' => 'open-jobs',
  'fi' => 'avoimet-tyopaikat',
  'sv' => 'lediga-jobb',
  'ru' => 'open-jobs',
];

$config['openid_connect.client.tunnistamo']['settings']['is_production'] = TRUE;
$config['helfi_proxy.settings']['tunnistamo_return_url'] = '/fi/avoimet-tyopaikat/openid-connect/tunnistamo';
