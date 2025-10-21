<?php
return [
  // MySQL
  'db_host' => '127.0.0.1',
  'db_name' => 'openclimate',
  'db_user' => 'openclimate',
  'db_pass' => 'changeme',

  // DHIS2
  'dhis2_base_url' => 'https://dhis2.example.org',
  'dhis2_username' => 'climate_bot',
  'dhis2_password' => 'changeme',

  // Publishing
  'publish_dhis2' => false, // set true to POST to DHIS2
  'dataset' => 'CLIMATE_DS',
  'default_coc' => 'default',

  // App
  'default_country' => 'ET',
];
