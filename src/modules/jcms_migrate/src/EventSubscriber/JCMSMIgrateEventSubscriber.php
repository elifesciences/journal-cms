<?php

namespace Drupal\jcms_migrate\EventSubscriber;

use Drupal\entityqueue\Entity\EntitySubqueue;
use Drupal\jcms_article\Entity\ArticleVersions;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Drupal\migrate\Event\MigratePreRowSaveEvent;
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
   * Check if migration is complete.
   *
   * @return bool
   */
  protected function migrationComplete() {
    $manager = \Drupal::service('plugin.manager.config_entity_migration');
    $plugins = $manager->createInstances([]);
    foreach ($plugins as $migration_id => $migration) {
      $map = $migration->getIdMap();
      $source_plugin = $migration->getSourcePlugin();
      $source_rows = $source_plugin->count();
      if ($source_rows >= 0) {
        $unprocessed = $source_rows - $map->processedCount();
        if ($unprocessed > 0) {
          return FALSE;
        }
      }
    }

    return TRUE;
  }

  /**
   * Code to run after migration.
   *
   * @param \Drupal\migrate\Event\MigrateImportEvent $event
   */
  public function onMigrateImport(MigrateImportEvent $event) {
    // Only populate community list if migration is complete.
    if (!$this->migrationComplete()) {
      return;
    }

    // Set community list items to those on: https://elifesciences.org/collections/early-career-researchers
    $community_list = file_get_contents(drupal_get_path('module', 'jcms_migrate') . '/migration_assets/community.json');
    $community_list = json_decode($community_list);
    foreach ($community_list as $community_item) {
      switch ($community_item->type) {
        case 'article':
          $crud_service = \Drupal::service('jcms_migrate.article_crud');
          if ($nid = $crud_service->getNodeIdByArticleId($community_item->source)) {
            $item = Node::load($nid);
          }
          else {
            $article_versions = new ArticleVersions($community_item->source);
            $item = $crud_service->createArticle($article_versions);
          }
          break;
        default:
          if ($items = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['uuid' => $community_item->source])) {
            $item = current($items);
          }
          else {
            $item = FALSE;
          }
      }

      if (!empty($item)) {
        $item->set('field_community_list', 1);
        $item->save();
      }
    }
  }
}
