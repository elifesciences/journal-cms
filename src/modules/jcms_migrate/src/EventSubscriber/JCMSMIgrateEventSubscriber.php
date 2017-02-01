<?php

namespace Drupal\jcms_migrate\EventSubscriber;

use Drupal\entityqueue\Entity\EntitySubqueue;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class JCMSMIgrateEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[MigrateEvents::POST_ROW_SAVE] = 'onPostRowSave';
    return $events;
  }

  /**
   * Code to run after a row has been saved.
   */
  public function onPostRowSave(MigratePostRowSaveEvent $event) {
    if ($event->getMigration()->id() == 'jcms_covers_db') {
      if ($event->getRow()->getDestinationProperty('counter') <= 3) {
        $subqueue = EntitySubqueue::load('covers');
        $items = ($subqueue->get('items')) ? $subqueue->get('items')->getValue() : [];
        if (count($items) < 3) {
          $ids = $event->getDestinationIdValues();
          $items[] = [
            'target_id' => $ids[0],
          ];
          $subqueue->set('items', $items);
          $subqueue->save();
        }
      }
    }
  }
}
