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
  '^[a-z0-9\-]+\.vagrantshare\.com$',
];
  
if (class_exists(\Composer\Autoload\ClassLoader::class)) {
  $loader = new \Composer\Autoload\ClassLoader();
  $loader->addPsr4('Drupal\\redis\\', 'modules/redis/src');
  $loader->register();
  $settings['bootstrap_container_definition'] = [
    'parameters' => [],
    'services' => [
      'cache.container' => [
        'class' => 'Drupal\redis\Cache\PhpRedis',
        'factory' => ['@cache.backend.redis', 'get'],
        'arguments' => ['container', '@redis', '@cache_tags_provider.container', '@serialization.phpserialize'],
      ],
      'cache_tags_provider.container' => [
        'class' => 'Drupal\redis\Cache\RedisCacheTagsChecksum',
        'arguments' => ['@redis.factory'],
      ],
      'redis' => [
        'class' => 'Redis',
      ],
      'cache.backend.redis' => [
        'class' => 'Drupal\redis\Cache\CacheBackendFactory',
        'arguments' => ['@redis.factory', '@cache_tags_provider.container', '@serialization.phpserialize'],
      ],
      'redis.factory' => [
        'class' => 'Drupal\redis\ClientFactory',
      ],
      'serialization.phpserialize' => [
        'class' => 'Drupal\Component\Serialization\PhpSerialize',
      ],
    ],
  ];
}

if (!drupal_installation_attempted()) {
  $settings['cache']['default'] = 'cache.backend.redis';
  $settings['redis.connection']['interface'] = 'PhpRedis';
  $settings['redis.connection']['host'] = '127.0.0.1';
  // Always set the fast backend for bootstrap, discover and config, otherwise
  // this gets lost when redis is enabled.
  $settings['cache']['bins']['bootstrap'] = 'cache.backend.chainedfast';
  $settings['cache']['bins']['discovery'] = 'cache.backend.chainedfast';
  $settings['cache']['bins']['config'] = 'cache.backend.chainedfast';
  $settings['container_yamls'][] = 'modules/redis/example.services.yml';
}
else {
  error_log('Redis cache backend is unavailable.');
}

$settings['file_private_path'] = './../private';

$settings['jcms_sqs_endpoint'] = 'http://localhost:4100';
$settings['jcms_sqs_queue'] = 'journal-cms--queue-local';
// Production template is 'arn:aws:sns:us-east-1:512686554592:bus-%s--dev'.
$settings['jcms_sns_topic_template'] = 'arn:aws:sns:local:000000000000:%s';
$settings['jcms_sqs_region'] = 'us-east-1';
$settings['jcms_gateway'] = 'https://prod--gateway.elifesciences.org';
$settings['jcms_all_articles_endpoint'] = $settings['jcms_gateway'] . '/articles';
$settings['jcms_articles_endpoint'] = $settings['jcms_gateway'] . '/articles/%s/versions';
$settings['jcms_metrics_endpoint'] = $settings['jcms_gateway'] . '/metrics/article/%s/%s';
$settings['jcms_article_fragment_images_endpoint'] = $settings['jcms_gateway'] . '/articles/%s/fragments/image';
$settings['jcms_article_auth_unpublished'] = NULL;
$settings['jcms_migrate_legacy_cms_images_bucket'] = 'prod-elife-legacy-cms-images';
$settings['jcms_iiif_base_uri'] = 'https://prod--iiif.elifesciences.org/journal-cms:';
// This folder should be relative to the sites/default/files folder.
$settings['jcms_iiif_mount'] = 'iiif';
$settings['jcms_rest_cache_max_age'] = 0;
