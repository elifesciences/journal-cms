<?php

namespace Drupal\jcms_rest\EventSubscriber;

use Drupal\Core\EventSubscriber\ExceptionJsonSubscriber;

/**
 * Handle eLife JSON exceptions the same as JSON exceptions.
 */
class ExceptionJcmsJsonSubscriber extends ExceptionJsonSubscriber {

  /**
   * {@inheritdoc}
   */
  protected function getHandledFormats() {
    return ['jcms_json'];
  }

}
