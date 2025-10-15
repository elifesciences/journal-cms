<?php

// Config sync directory.
$settings['config_sync_directory'] = __DIR__.'/../../../sync';
$settings['file_private_path'] = __DIR__.'/../../../private';

// Hash salt.
$settings['hash_salt'] = getenv('DRUPAL_HASH_SALT');

//Twig cache directory
$settings['php_storage']['twig']['directory'] = '/tmp/twig';
$settings['php_storage']['twig']['secret'] = $settings['hash_salt'];

// Disallow access to update.php by anonymous users.
$settings['update_free_access'] = FALSE;

// Other helpful settings.
$settings['container_yamls'][] = "{$app_root}/{$site_path}/services.yml";

// Database connection.
$databases['default']['default'] = [
  'database' => getenv('DRUPAL_DATABASE_NAME'),
  'username' => getenv('DRUPAL_DATABASE_USERNAME'),
  'password' => getenv('DRUPAL_DATABASE_PASSWORD'),
  'prefix' => '',
  'host' => getenv('DRUPAL_DATABASE_HOST'),
  'port' => getenv('DRUPAL_DATABASE_PORT'),
  'namespace' => 'Drupal\Core\Database\Driver\mysql',
  'driver' => 'mysql',
];

$settings['trusted_host_patterns'] = ['.*'];

if (getenv('REDIS_HOST')) {
  $settings['cache']['default'] = 'cache.backend.redis';
  $settings['redis.connection']['interface'] = 'PhpRedis';
  $settings['redis.connection']['host'] = getenv('REDIS_HOST');
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

if (getenv('JCMS_JOURNAL_PATH')) {
  $settings['journal_path'] = getenv('JCMS_JOURNAL_PATH');
}

if (getenv('JCMS_JOURNAL_PREVIEW')) {
  $settings['journal_preview'] = getenv('JCMS_JOURNAL_PREVIEW');
}


if (getenv('JCMS_SQS_ENDPOINT')) {
  $settings['jcms_sqs_endpoint'] = getenv('JCMS_SQS_ENDPOINT');
} else {
  $settings['jcms_sqs_endpoint'] = null;
}

$settings['jcms_sqs_queue'] = getenv('JCMS_SQS_QUEUE');
$settings['jcms_sqs_region'] = getenv('JCMS_SQS_REGION');
$settings['jcms_sns_topic_template'] =  getenv('JCMS_TOPIC_TEMPLATE');

$settings['jcms_gateway'] = getenv('JCMS_API_GATEWAY');
$settings['jcms_articles_endpoint'] = $settings['jcms_gateway'] . '/articles/%s/versions';
$settings['jcms_metrics_endpoint'] = $settings['jcms_gateway'] . '/metrics/article/%s/%s';
$settings['jcms_all_articles_endpoint'] = $settings['jcms_gateway'] . '/articles';
$settings['jcms_all_reviewed_preprints_endpoint'] = $settings['jcms_gateway'] . '/reviewed-preprints';
$settings['jcms_all_digests_endpoint'] = $settings['jcms_gateway'] . '/digests';
$settings['jcms_article_fragments_endpoint'] = $settings['jcms_gateway'] . '/articles/%s/fragments/%s';

// migration gateway can be overridden
$settings['jcms_articles_endpoint_for_migration'] = (getenv('JCMS_API_GATEWAY_FOR_MIGRATION') ?: $settings['jcms_gateway']) . $settings['jcms_articles_endpoint'];

if (getenv('JCMS_AUTH_UNPUBLISHED')) {
  $settings['jcms_article_auth_unpublished'] = getenv('JCMS_AUTH_UNPUBLISHED');
} else {
  $settings['jcms_article_auth_unpublished'] = null;
}

if (getenv('JCMS_IIIF_BASE_URI') && getenv('JCMS_IIIF_MOUNT')) {
  $settings['jcms_iiif_base_uri'] = getenv('JCMS_IIIF_BASE_URI');
  $settings['jcms_iiif_mount'] = getenv('JCMS_IIIF_MOUNT');
} else {
  $settings['jcms_iiif_base_uri'] = null;
}
$settings['jcms_rest_cache_max_age'] = 300;

if (getenv('JCMS_BASE_URL')) {
  $settings['base_url'] = getenv('JCMS_BASE_URL');
}
