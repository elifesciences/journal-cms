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

$settings['jcms_sqs_endpoint'] = 'http://localhost:4100';
$settings['jcms_sqs_queue'] = 'journal-cms--queue-local';
// Production template is 'arn:aws:sns:us-east-1:512686554592:bus-%s--dev'.
$settings['jcms_sns_topic_template'] = 'arn:aws:sns:local:000000000000:%s';
$settings['jcms_sqs_region'] = 'us-east-1';
$settings['jcms_articles_endpoint'] = 'https://prod--gateway.elifesciences.org/articles/%s/versions';
