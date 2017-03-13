<?php

namespace Drupal\jcms_migrate\EventSubscriber;

use Drupal\entityqueue\Entity\EntitySubqueue;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Drupal\node\Entity\Node;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class JCMSMIgrateEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[MigrateEvents::POST_ROW_SAVE] = 'onPostRowSave';
    $events[MigrateEvents::POST_IMPORT][] = ['onMigrateImport'];
    return $events;
  }

  /**
   * Code to run after a row has been saved.
   *
   * @param \Drupal\migrate\Event\MigratePostRowSaveEvent $event
   */
  public function onPostRowSave(MigratePostRowSaveEvent $event) {
    if ($event->getMigration()->id() == 'jcms_covers_db') {
      if ($event->getRow()->getDestinationProperty('counter') <= 3) {
        foreach (['covers', 'covers_preview'] as $subqueue_id) {
          $subqueue = EntitySubqueue::load($subqueue_id);
          $items = ($subqueue->get('items')) ? $subqueue->get('items')->getValue() : [];
          if (count($items) < 3) {
            $ids = $event->getDestinationIdValues();
            $items[] = [
              'target_id' => $ids[0],
            ];
            $subqueue->set('items', $items);
            if ($subqueue_id == 'covers_preview') {
              $subqueue->set('field_covers_active_items', count($items));
            }
            $subqueue->save();
          }
        }
      }
    }
  }

  /**
   * Code to run after migration.
   *
   * @param \Drupal\migrate\Event\MigrateImportEvent $event
   */
  public function onMigrateImport(MigrateImportEvent $event) {
    // Set 2 of each content type as a community item at random.
    $limit = 2;
    $types = [
      'article',
      'blog_article',
      'collection',
      'event',
      'interview',
      'labs_experiment',
      'podcast_episode',
    ];

    foreach ($types as $type) {
      $query = \Drupal::entityQuery('node')
        ->condition('status', NODE_PUBLISHED)
        ->condition('type', $type);

      $new_query = clone $query;

      $query->condition('field_community_list.value', 1);
      $count = $query->count()->execute();

      if ($count < $limit) {
        $new_limit = $limit - $count;

        $new_query->condition('field_community_list.value', 0);
        $new_query->range(0, $new_limit);
        $new_query->addTag('random');

        $nids = $new_query->execute();

        if ($nids) {
          $nodes = Node::loadMultiple($nids);
          foreach ($nodes as $node) {
            $node->set('field_community_list', 1);
            $node->save();
          }
        }
      }
    }
  }
}
