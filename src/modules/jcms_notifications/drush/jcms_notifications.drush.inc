<?php

/**
 * @file
 * Drush commands for the module.
 */

/**
 * Implements hook_drush_command().
 */
function jcms_notifications_drush_command() {
  $items = [];
  $items['article-import'] = [
    'description' => 'Imports articles from Lax via SQS.',
    'drupal dependencies' => ['jcms_notifications'],
    'aliases' => ['ai'],
    'arguments' => [
      'lrp' => 'Long running process or not. Defaults to false.',
    ],
    'examples' => [
      'drush article-import 1' => 'Import articles from Lax as a long running process.',
      'drush article-import' => 'Import articles from Lax and return a message when finished.',
    ],
  ];
  $items['article-import-all'] = [
    'description' => 'Imports all articles from Lax.',
    'options' => [
      'limit' => 'Limit on the number of items to process in each import.',
      'skip-updates' => 'Do not attempt to update articles that exist already.',
    ],
    'drupal dependencies' => ['jcms_notifications'],
    'aliases' => ['aia'],
    'examples' => [
      'drush article-import-all' => 'Import all articles from Lax and return a message when finished.',
      'drush article-import-all --limit=500' => 'Import first 500 articles from Lax and return a message when finished.',
      'drush article-import-all --skip-updates' => 'Import all articles from Lax, but skip over articles that exist already, and return a message when finished.',
    ],
  ];
  $items['send-notifications'] = [
    'description' => 'Gets notifications from the database and send them to SNS.',
    'options' => [
      'iterations' => 'Limit on the number of iterations made then terminate.',
      'sleep' => 'The amount of time in seconds to sleep before iterating over the notifications table again (defaults to 30 seconds).',
    ],
    'drupal dependencies' => ['jcms_notifications'],
    'aliases' => ['sendn'],
    'examples' => [
      'drush send-notifications' => 'Long running process that iterates infinitely over the notifications table .',
      'drush send-notifications --iterations=20' => 'Iterate over the notifications table 20 times then stop.',
      'drush send-notifications --sleep=10' => 'Sleep for 10 seconds after each iteration.',
    ],
  ];
  return $items;
}

/**
 * Callback function drush_jcms_notifications_article_import_all().
 */
function drush_jcms_notifications_article_import_all() {
  $fetch_service = \Drupal::service('jcms_article.fetch_article');
  $fetch_versions_service = \Drupal::service('jcms_article.fetch_article_versions');
  $crud_service = \Drupal::service('jcms_article.article_crud');
  drush_print(dt('Fetching all article IDs. This may take a few minutes.'));
  $limit = drush_get_option('limit') ? (int) drush_get_option('limit') : NULL;
  if (!empty($limit)) {
    $fetch_service->setLimit($limit);
  }
  $ids = $fetch_service->getAllArticleIDs();
  drush_print(dt('Received !count article IDs to process.', ['!count' => count($ids)]));
  if ($ids) {
    $time_start = microtime(TRUE);
    foreach ($ids as $num => $id) {
      $articleVersions = $fetch_versions_service->getArticleVersions($id);
      if (drush_get_option('skip-updates')) {
        $crud_service->skipUpdates();
      }
      $crud_service->crudArticle($articleVersions);
      drush_print(dt('Processed article !article_id (!num of !count)', [
        '!article_id' => $id,
        '!num' => $num + 1,
        '!count' => count($ids),
      ]));
    }
    $time_end = microtime(TRUE);
    $time = round($time_end - $time_start, 0);
    drush_print(dt('Processed !count articles in !minutes minutes !seconds seconds.', [
      '!count' => count($ids),
      '!minutes' => floor($time / 60),
      '!seconds' => round($time % 60),
    ]));
  }
}

/**
 * Callback function drush_jcms_notifications_article_import().
 */
function drush_jcms_notifications_article_import($lrp = FALSE) {
  $queue_service = \Drupal::service('jcms_notifications.queue_service');
  $fetch_service = \Drupal::service('jcms_article.fetch_article_versions');
  $crud_service = \Drupal::service('jcms_article.article_crud');
  $count = 0;
  while (TRUE) {
    $message = $queue_service->getMessage();
    // If this isn't a long running process and the message is null.
    if ($message === NULL && !$lrp) {
      break;
    }
    if ($message !== NULL) {
      drush_print(dt('Received message !message', ['!message' => var_export($message, TRUE)]));
      $id = $message->getId();
      if ($id) {
        $articleVersions = $fetch_service->getArticleVersions($id);
        $crud_service->crudArticle($articleVersions);
        drush_print(dt('Processed message !message_id', ['!message_id' => $message->getMessageId()]));
        $queue_service->deleteMessage($message);
        drush_print(dt('Deleted from the queue message !message_id', ['!message_id' => $message->getMessageId()]));
        $count++;
      }
    }
  }
  drush_print(dt('Imported !count articles.', ['!count' => $count]));
}

/**
 * Callback function drush_jcms_notifications_get_notifications().
 */
function drush_jcms_notifications_send_notifications() {
  $storage = \Drupal::service('jcms_notifications.notification_storage');
  $sns_crud = \Drupal::service('jcms_notifications.node_crud_notification_service');
  $iterations = drush_get_option('iterations') ?: NULL;
  $sleep = drush_get_option('sleep') ?: 30;
  $i = 0;
  while(TRUE) {
    if ($iterations) {
      if ($i >= $iterations) {
        drush_print(dt('Finished after !iterations iterations.', ['!iterations' => $iterations]));
        break;
      }
      $i++;
    }
    // Read the table, get the IDs.
    $notifications = $storage->getNotificationNids();
    // Iterate through them.
    $nodes = node_load_multiple($notifications);
    $nids = [];
    foreach ($nodes as $node) {
      $nids[] = $node->id();
      $sns_crud->sendMessage($node);
      drush_print(dt('Sent notification for !nid', ['!nid' => $node->id()]));
    }
    $storage->deleteNotificationNids($nids);
    sleep($sleep);
  }
}
