<?php

namespace Drupal\jcms_rest;

/**
 * Retrieve the path to the content schema.
 */
class SchemaPath {

  /**
   * Output the path to the content schema.
   */
  public function __toString() {
    return \ComposerLocator::getPath('elife/api') . '/dist/model';
  }

}
