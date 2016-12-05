<?php

/**
 * @file
 * Drush commands for the module.
 */

/**
 * Implements hook_drush_command().
 */
function jcms_notifications_drush_command() {
  $items = array();
  $items['article-import'] = array(
    'description' => 'Imports articles from Lax.',
    'drupal dependencies' => array('jcms_notifications'),
    'aliases' => array('ai'),
    'arguments' => array(
      'lrp' => 'Long running process or not. Defaults to false.',
    ),
    'examples' => array(
      'drush article-import 1' => 'Import articles from Lax as a long running process.',
      'drush article-import' => 'Import articles from Lax and return a message when finished.',
    ),
  );
  return $items;
}

/**
 * Callback function drush_jcms_notifications_article_import().
 */
function drush_jcms_notifications_article_import($lrp = FALSE) {
  $queue_service = \Drupal::service('jcms_notifications.queue_service');
  $fetch_service = \Drupal::service('jcms_notifications.fetch_article_service');
  $crud_service = \Drupal::service('jcms_notifications.article_crud_service');
  $count = 0;
  while (TRUE) {
    $message = $queue_service->getMessage();
    // If this isn't a long running process and the message is null.
    if ($message === NULL && !$lrp) {
      break;
    }
    if ($message !== NULL) {
      drush_print(dt('Received message !message', ['!message' => var_export($message, true)]));
      $id = $message->getId();
      if ($id) {
        $article = $fetch_service->getArticle($id);
        $crud_service->crudArticle($article);
        drush_print(dt('Processed message !message_id', ['!message_id' => $message->getMessageId()]));
        $queue_service->deleteMessage($message);
        drush_print(dt('Deleted from the queue message !message_id', ['!message_id' => $message->getMessageId()]));
        $count++;
      }
    }
  }
  drush_print(dt('Imported !count articles.', array('!count' => $count)));
}

