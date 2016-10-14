<?php

$databases['default']['default'] = [
  'database' => 'journal_cms',
  'username' => 'journal_cms',
  'password' => 'journal_cms',
  'prefix' => '',
  'host' => 'localhost',
  'port' => '3306',
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'driver' => 'mysql',
];

$databases['legacy_cms']['default'] = [
  'database' => 'legacy_cms',
  'username' => 'legacy_cms',
  'password' => 'legacy_cms',
  'prefix' => '',
  'host' => 'localhost',
  'port' => '3306',
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'driver' => 'mysql',
];

$settings['trusted_host_patterns'] = [
  '^journal\-cms\.local$',
];
