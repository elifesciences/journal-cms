<?php

namespace Drupal\jcms_notifications\Commands;

use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drush\Commands\DrushCommands;
use GuzzleHttp\Exception\RequestException;

/**
 * A Drush commandfile.
 */
class JcmsNotificationsCommands extends DrushCommands {

  /**
   * Imports articles from Lax via SQS (Deprecated: use drush message-import).
   *
   * @param bool $lrp
   *   Long running process or not. Defaults to false.
   *
   * @usage drush article-import 1
   *   Import articles from Lax as a long running process.
   * @usage drush article-import
   *   Import articles from Lax and return a message when finished.
   * @validate-module-enabled jcms_notifications
   *
   * @command article:import
   * @aliases ai,article-import
   */
  public function articleImport($lrp = FALSE) {
    $this->messageImport($lrp);
  }

  /**
   * Imports items from Lax and digests via input.
   *
   * @param string $id
   *   ID of article or digest.
   * @param string $type
   *   Article or digest.
   *
   * @usage drush message-import-force 00288 article
   *   Retrieve the current snippet for article 00288.
   * @usage drush message-import-force 00288 digest
   *   Retrieve the current snippet for digest 00288.
   * @validate-module-enabled jcms_notifications
   *
   * @command message:import-force
   * @aliases message-import-force
   */
  public function messageImportForce(string $id, string $type) {
    $logger = \Drupal::logger('jcms_message_import');
    $queue_service = \Drupal::service('jcms_notifications.queue_service');
    $article_service = \Drupal::service('jcms_article.fetch_article_versions');
    $reviewed_preprint_service = \Drupal::service('jcms_article.fetch_reviewed_preprint');
    $digest_service = \Drupal::service('jcms_digest.fetch_digest');
    $article_crud_service = \Drupal::service('jcms_article.article_crud');
    $digest_crud_service = \Drupal::service('jcms_digest.digest_crud');
    $reviewed_preprint_crud_service = \Drupal::service('jcms_article.reviewed_preprint_crud');
    $message = $queue_service->prepareMessage($id, $type);
    $logger->info('Prepared message', ['message' => $message->getMessage()]);

    try {
      switch ($message->getType()) {
        case 'article':
          $articleVersions = $article_service->getArticleVersions($message->getId());
          $article_crud_service->crudArticle($articleVersions);
          $logger->info('Article snippet updated', ['message' => $message->getMessage()]);
          $this->output()->writeln(dt('Article snippet updated (!id)', [
            '!id' => $message->getId(),
          ]));
          break;

        case 'reviewed-preprint':
          $reviewedPreprint = $reviewed_preprint_service->getReviewedPreprintById($message->getId());
          $reviewed_preprint_crud_service->crudReviewedPreprint($reviewedPreprint);
          $logger->info('Reviewed preprint snippet updated', ['message' => $message->getMessage()]);
          $this->output()->writeln(dt('Reviewed preprint snippet updated (!id)', [
            '!id' => $message->getId(),
          ]));
          break;

        case 'digest':
          $digest = $digest_service->getDigestById($message->getId());
          $digest_crud_service->crudDigest($digest);
          $logger->info('Digest snippet updated', ['message' => $message->getMessage()]);
          $this->output()->writeln(dt('Digest snippet updated (!id)', [
            '!id' => $message->getId(),
          ]));
          break;
      }
      $logger->info('Processed message', ['message_id' => $message->getMessageId()]);
    }
    catch (Exception $e) {
      $e_message = "Message: {$e->getMessage()}\n";
      $e_line = "Line: {$e->getLine()}\n";
      $e_trace = "Trace: {$e->getTraceAsString()}\n";
      $error = $e_message . $e_line . $e_trace;
      error_log($error);
      if (!$e instanceof RequestException) {
        throw $e;
      }
    }
  }

  /**
   * Imports items from Lax and digests via SQS.
   *
   * @param bool $lrp
   *   Long running process or not. Defaults to false.
   *
   * @usage drush message-import 1
   *   Import items from Lax and digest as a long running process.
   * @usage drush message-import
   *   Import items from Lax and digest and return a message when finished.
   * @validate-module-enabled jcms_notifications
   *
   * @command message:import
   * @aliases message-import
   */
  public function messageImport($lrp = FALSE) {
    $logger = \Drupal::logger('jcms_message_import');
    $queue_service = \Drupal::service('jcms_notifications.queue_service');
    $article_service = \Drupal::service('jcms_article.fetch_article_versions');
    $metrics_service = \Drupal::service('jcms_article.fetch_article_metrics');
    $digest_service = \Drupal::service('jcms_digest.fetch_digest');
    $reviewed_preprint_service = \Drupal::service('jcms_article.fetch_reviewed_preprint');
    $article_crud_service = \Drupal::service('jcms_article.article_crud');
    $digest_crud_service = \Drupal::service('jcms_digest.digest_crud');
    $reviewed_preprint_crud_service = \Drupal::service('jcms_article.reviewed_preprint_crud');
    $limit_service = \Drupal::service('jcms_notifications.limit_service');
    $logger->info('Started');
    $count = 0;
    while (!$limit_service()) {
      $message = $queue_service->getMessage();
      // If this isn't a long running process and the message is null.
      if ($message === NULL && !$lrp) {
        break;
      }
      if ($message !== NULL) {
        $logger->info('Received message', ['message' => $message->getMessage()]);
        $id = $message->getId();

        // Temporary tolerance in id while articles and metrics id are
        // inconsistent.
        if (strlen($id) < 5 &&
          in_array($message->getType(), ['article', 'metrics'])) {
          $id = str_pad($id, 5, '0', STR_PAD_LEFT);
          $logger->warning('Id for type is too short. Changing before to after.', [
            'type' => $message->getType(),
            'before' => $message->getId(),
            'after' => $id,
          ]);
        }

        try {
          switch ($message->getType()) {
            // Process article sqs items.
            case 'article':
              $articleVersions = $article_service->getArticleVersions($id);
              $article_crud_service->crudArticle($articleVersions);
              $logger->info('Article snippet updated', ['message' => $message->getMessage()]);
              break;

            case 'reviewed-preprint':
              $reviewedPreprint = $reviewed_preprint_service->getReviewedPreprintById($id);
              $reviewed_preprint_crud_service->crudReviewedPreprint($reviewedPreprint);
              $logger->info('Reviewed preprint snippet updated', ['message' => $message->getMessage()]);
              break;

            case 'digest':
              $digest = $digest_service->getDigestById($id);
              $digest_crud_service->crudDigest($digest);
              $logger->info('Digest snippet updated', ['message' => $message->getMessage()]);
              break;

            case 'metrics':
              // Process article views-downloads metrics sqs items.
              $body = $message->getMessage();
              if ($body['contentType'] == 'article' && $body['metric'] == 'views-downloads') {
                $nid = $article_crud_service->getNodeIdByArticleId($id);
                if ($nid === 0) {
                  $articleVersions = $article_service->getArticleVersions($id);
                  $node = $article_crud_service->crudArticle($articleVersions);
                }
                else {
                  $node = Node::load($nid);
                }

                $metrics = $metrics_service->getArticleMetrics($id);
                if ((int) $node->get('field_page_views')->getString() != $metrics->getPageViews()) {
                  $logger->info('Adjusted metrics', [
                    'id' => $id,
                    'metrics' => $metrics->getPageViews(),
                  ]);
                  $node->set('field_page_views', $metrics->getPageViews());
                  $node->save();
                }
              }
              break;
          }
          $logger->info('Processed message', ['message_id' => $message->getMessageId()]);
        }
        catch (Exception $e) {
          $e_message = "Message: {$e->getMessage()}\n";
          $e_line = "Line: {$e->getLine()}\n";
          $e_trace = "Trace: {$e->getTraceAsString()}\n";
          $error = $e_message . $e_line . $e_trace;
          error_log($error);
          if (!$e instanceof RequestException) {
            throw $e;
          }
        }
        finally {
          $queue_service->deleteMessage($message);
          $logger->info('Deleted message from the queue', ['message_id' => $message->getMessageId()]);
          $count++;
        }
      }
    }
    $logger->info('Imported  queue item(s).', ['count' => $count]);
  }

  /**
   * Create an SQS message.
   *
   * @param string $id
   *   ID of article or digest.
   * @param string $type
   *   Article or digest.
   *
   * @validate-module-enabled jcms_notifications
   *
   * @command create:sqs-message
   * @aliases create-sqs-message
   */
  public function createSqsMessage(string $id, string $type) {
    $queue_service = \Drupal::service('jcms_notifications.queue_service');
    $message = $queue_service->prepareMessage($id, $type);
    $queue_service->sendMessage($message);
    $this->output()->writeln(dt('SQS message created: !message', [
      '!message' => json_encode($message->getMessage()),
    ]));
  }

  /**
   * Imports all articles from Lax.
   *
   * @param array $options
   *   Array of options whose values come from cli, aliases, config, etc.
   *
   * @option limit
   *   Limit on the number of items to process in each import.
   * @option skip-updates
   *   Do not attempt to update articles that exist already.
   * @usage drush article-import-all
   *   Import all articles from Lax and return a message when finished.
   * @usage drush article-import-all --limit=500
   *   Import first 500 articles from Lax and return a message when finished.
   * @usage drush article-import-all --skip-updates
   *   Import all articles from Lax, but skip over articles that exist already,
   * and return a message when finished.
   * @validate-module-enabled jcms_notifications
   *
   * @command article:import-all
   * @aliases aia,article-import-all
   */
  public function articleImportAll(array $options = [
    'limit' => NULL,
    'skip-updates' => NULL,
  ]) {
    $fetch_service = \Drupal::service('jcms_article.fetch_article');
    $fetch_versions_service = \Drupal::service('jcms_article.fetch_article_versions');
    $crud_service = \Drupal::service('jcms_article.article_crud');

    $this->output()->writeln(dt('Fetching all article IDs. This may take a few minutes.'));
    $limit = $options['limit'] ? (int) $options['limit'] : NULL;
    if (!empty($limit)) {
      $fetch_service->setLimit($limit);
    }
    $ids = $fetch_service->getAllArticleIds();
    $this->output()->writeln(dt('Received !count article IDs to process.', ['!count' => count($ids)]));
    if ($ids) {
      $time_start = microtime(TRUE);
      foreach ($ids as $num => $id) {
        $articleVersions = $fetch_versions_service->getArticleVersions($id);
        if ($options['skip-updates']) {
          $crud_service->skipUpdates();
        }
        $crud_service->crudArticle($articleVersions);
        $this->output()->writeln(dt('Processed article !article_id (!num of !count)', [
          '!article_id' => $id,
          '!num' => $num + 1,
          '!count' => count($ids),
        ]));
      }
      $time_end = microtime(TRUE);
      $time = round($time_end - $time_start, 0);
      $this->output()->writeln(dt('Processed !count articles in !minutes minutes !seconds seconds.', [
        '!count' => count($ids),
        '!minutes' => floor($time / 60),
        '!seconds' => round($time % 60),
      ]));
    }
  }

  /**
   * Imports all reviewed preprints.
   *
   * @param array $options
   *   Array of options whose values come from cli, aliases, config, etc.
   *
   * @option limit
   *   Limit on the number of items to process in each import.
   * @option skip-updates
   *   Do not attempt to update reviewed preprints that exist already.
   * @option start-date
   *   Apply start-date filter.
   * @usage drush reviewed-preprint-import-all
   *   Import all reviewed preprints and return a message when finished.
   * @usage drush reviewed-preprint-import-all --start-date=today
   *   Import all reviewed preprints updated after start-date and return a message when finished.
   * @usage drush reviewed-preprint-import-all --limit=500
   *   Import first 500 reviewed preprints and return a message when finished.
   * @usage drush reviewed-preprint-import-all --skip-updates
   *   Import all reviewed preprints, but skip over reviewed preprints that
   * exist already, and return a message when finished.
   * @validate-module-enabled jcms_notifications
   *
   * @command reviewed-preprint:import-all
   * @aliases rpia,reviewed-preprint-import-all
   */
  public function reviewedPreprintImportAll(array $options = [
    'limit' => NULL,
    'skip-updates' => NULL,
  ]) {
    $fetch_service = \Drupal::service('jcms_article.fetch_reviewed_preprint');
    $crud_service = \Drupal::service('jcms_article.reviewed_preprint_crud');

    $this->output()->writeln(dt('Fetching all reviewed preprint IDs. This may take a few minutes.'));
    $limit = $options['limit'] ? (int) $options['limit'] : NULL;
    if (!empty($limit)) {
      $fetch_service->setLimit($limit);
    }
    $start_date = $options['start-date'] ? date('Y-m-d', strtotime(['start-date'])) : NULL;
    $reviewedPreprints = $fetch_service->getAllReviewedPreprints($start_date);
    $this->output()->writeln(dt('Received !count reviewed preprint IDs to process.', ['!count' => count($reviewedPreprints)]));
    if ($reviewedPreprints) {
      $time_start = microtime(TRUE);
      $num = 0;
      /** @var \Drupal\jcms_article\Entity\ReviewedPreprint $reviewedPreprint */
      foreach ($reviewedPreprints as $reviewedPreprint) {
        if ($options['skip-updates']) {
          $crud_service->skipUpdates();
        }
        $crud_service->crudReviewedPreprint($reviewedPreprint);
        $this->output()->writeln(dt('Processed reviewed preprint !reviewed_preprint_id (!num of !count)', [
          '!reviewed_preprint_id' => $reviewedPreprint->getId(),
          '!num' => ++$num,
          '!count' => count($reviewedPreprints),
        ]));
      }
      $time_end = microtime(TRUE);
      $time = round($time_end - $time_start, 0);
      $this->output()->writeln(dt('Processed !count reviewed preprints in !minutes minutes !seconds seconds.', [
        '!count' => count($reviewedPreprints),
        '!minutes' => floor($time / 60),
        '!seconds' => round($time % 60),
      ]));
    }
  }

  /**
   * Imports all digests.
   *
   * @param array $options
   *   Array of options whose values come from cli, aliases, config, etc.
   *
   * @option limit
   *   Limit on the number of items to process in each import.
   * @option skip-updates
   *   Do not attempt to update articles that exist already.
   * @usage drush digest-import-all
   *   Import all digests and return a message when finished.
   * @usage drush digest-import-all --limit=10
   *   Import first 10 digests and return a message when finished.
   * @usage drush digest-import-all --skip-updates
   *   Import all digests, but skip over digests that exist already, and
   * return a message when finished.
   * @validate-module-enabled jcms_notifications
   *
   * @command digest:import-all
   * @aliases dia,digest-import-all
   */
  public function digestImportAll(array $options = [
    'limit' => NULL,
    'skip-updates' => NULL,
  ]) {
    $fetch_service = \Drupal::service('jcms_digest.fetch_digest');
    $crud_service = \Drupal::service('jcms_digest.digest_crud');
    $this->output()->writeln(dt('Fetching all digest IDs. This may take a few minutes.'));
    $limit = $options['limit'] ? (int) $options['limit'] : NULL;
    if (!empty($limit)) {
      $fetch_service->setLimit($limit);
    }
    $digests = $fetch_service->getAllDigests();
    $this->output()->writeln(dt('Received !count digest IDs to process.', ['!count' => count($digests)]));
    if ($digests) {
      $time_start = microtime(TRUE);
      $num = 0;
      /** @var \Drupal\jcms_digest\Entity\Digest $digest */
      foreach ($digests as $digest) {
        if ($options['skip-updates']) {
          $crud_service->skipUpdates();
        }
        $crud_service->crudDigest($digest);
        $this->output()->writeln(dt('Processed digest !digest_id (!num of !count)', [
          '!digest_id' => $digest->getId(),
          '!num' => ++$num,
          '!count' => count($digests),
        ]));
      }
      $time_end = microtime(TRUE);
      $time = round($time_end - $time_start, 0);
      $this->output()->writeln(dt('Processed !count digests in !minutes minutes !seconds seconds.', [
        '!count' => count($digests),
        '!minutes' => floor($time / 60),
        '!seconds' => round($time % 60),
      ]));
    }
  }

  /**
   * Imports all metrics for articles in journal-cms.
   *
   * @param array $options
   *   Array of options whose values come from cli, aliases, config, etc.
   *
   * @option limit
   *   Limit on the number of items to process in each import.
   * @option skip-updates
   *   Do not attempt to update articles that have a metric value already.
   * @usage drush article-metrics-import-all
   *   Import all article metrics in journal-cms and return a message when
   * finished.
   * @usage drush article-metrics-import-all --limit=500
   *   Import first 500 article metrics in journal-cms and return a message
   * when finished.
   * @usage drush article-metrics-import-all --skip-updates
   *   Import all article metrics in journal-cms, but skip over articles that
   * we have a metric for already, and return a message when finished.
   * @validate-module-enabled jcms_notifications
   *
   * @command article:metrics-import-all
   * @aliases amia,article-metrics-import-all
   */
  public function articleMetricsImportAll(array $options = [
    'limit' => NULL,
    'skip-updates' => NULL,
  ]) {
    $metrics_service = \Drupal::service('jcms_article.fetch_article_metrics');
    $this->output()->writeln(dt('Fetching article metrics. This may take a few minutes.'));
    $limit = $options['limit'] ? (int) $options['limit'] : NULL;

    $query = \Drupal::entityQuery('node')
      ->accessCheck(TRUE)
      ->condition('type', 'article');
    if (!empty($limit)) {
      $query->range(0, $limit);
    }
    if ($options['skip-updates']) {
      $query->condition('field_page_views.value', 0);
    }
    $nids = $query->execute();
    /** @var \Drupal\node\Entity\Node[] $nodes */
    $nodes = Node::loadMultiple($nids);
    $this->output()->writeln(dt('Received !count article metrics to process.', ['!count' => count($nids)]));
    if ($nodes) {
      $time_start = microtime(TRUE);
      $num = 0;
      foreach ($nodes as $nid => $node) {
        $articleMetrics = $metrics_service->getArticleMetrics($node->label());
        if ((int) $node->get('field_page_views')->getString() != $articleMetrics->getPageViews()) {
          $node->set('field_page_views', $articleMetrics->getPageViews());
          $node->save();
        }
        $this->output()->writeln(dt('Processed article metrics for !article_id (!num of !count)', [
          '!article_id' => $node->label(),
          '!num' => ++$num,
          '!count' => count($nids),
        ]));
      }
      $time_end = microtime(TRUE);
      $time = round($time_end - $time_start, 0);
      $this->output()->writeln(dt('Processed !count article metrics in !minutes minutes !seconds seconds.', [
        '!count' => count($nids),
        '!minutes' => floor($time / 60),
        '!seconds' => round($time % 60),
      ]));
    }
  }

  /**
   * Gets notifications from the database and send them to SNS.
   *
   * @param array $options
   *   Array of options whose values come from cli, aliases, config, etc.
   *
   * @option iterations
   *   Limit on the number of iterations made then terminate.
   * @option sleep
   *   The amount of time in seconds to sleep before iterating over the
   * notifications table again (defaults to 30 seconds).
   * @option delay
   *   The amount of time in seconds to sleep before sending the message after
   * receiving and loading the entities (defaults to 2 seconds).
   * @usage drush send-notifications
   *   Long running process that iterates infinitely over the notifications
   * table.
   * @usage drush send-notifications --iterations=20
   *   Iterate over the notifications table 20 times then stop.
   * @usage drush send-notifications --sleep=10
   *   Sleep for 10 seconds after each iteration.
   * @usage drush send-notifications --delay=2
   *   Sleep for 2 seconds before sending.
   * @validate-module-enabled jcms_notifications
   *
   * @command send:notifications
   * @aliases sendn,send-notifications
   */
  public function sendNotifications(array $options = [
    'iterations' => NULL,
    'sleep' => NULL,
    'delay' => NULL,
  ]) {
    $logger = \Drupal::logger('jcms_send_notifications');
    $storage = \Drupal::service('jcms_notifications.notification_storage');
    $sns_crud = \Drupal::service('jcms_notifications.entity_crud_notification_service');
    $limit_service = \Drupal::service('jcms_notifications.limit_service');

    $iterations = $options['iterations'] ?: NULL;
    $sleep = $options['sleep'] ?: 30;
    $delay = $options['delay'] ?: 2;

    $i = 0;
    $logger->info('Started');
    while (!$limit_service()) {
      if ($iterations) {
        if ($i >= $iterations) {
          $logger->info('Finished.', ['!iterations' => $iterations]);
          break;
        }
        $i++;
      }
      // Read the table, get the IDs.
      $node_notifications = $storage->getNotificationEntityIds('node');
      $nodes = Node::loadMultiple($node_notifications);
      $term_notifications = $storage->getNotificationEntityIds('taxonomy_term');
      $terms = Term::loadMultiple($term_notifications);
      $entities = array_merge(array_values($nodes), array_values($terms));
      sleep($delay);
      // Iterate through them.
      $entity_ids = [];
      foreach ($entities as $entity) {
        $entity_ids[] = $entity->id();
        $busMessage = $sns_crud->sendMessage($entity);
        $logger->info('Sent notification', [
          'message' => json_decode($busMessage->getMessageJson(), TRUE),
          'etid' => $entity->getEntityTypeId(),
          'eid' => $entity->id(),
        ]);
      }
      $storage->deleteNotificationEntityIds($entity_ids);
      sleep($sleep);
    }
    $logger->info('Exiting because of limits reached.');
  }

}
