<?php

$databases['default']['default'] = [
  'database' => 'journal_cms',
  'username' => 'root',
  'password' => '',
  'prefix' => '',
  'host' => 'localhost',
  'port' => '3306',
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'driver' => 'mysql',
];

$settings['jcms_migrate_legacy_cms_images_bucket'] = 'prod-elife-legacy-cms-images';
