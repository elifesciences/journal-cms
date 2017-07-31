<?php

namespace Drupal\jcms_rest\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Response subscriber to override setting of X-Generator header tag.
 */
class ResponseGeneratorSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [];
  }

}
