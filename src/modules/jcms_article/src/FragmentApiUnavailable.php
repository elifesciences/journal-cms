<?php

namespace Drupal\jcms_article;

/**
 * Exception class for FragmentApiUnavailable.
 */
class FragmentApiUnavailable extends \Exception {

  /**
   * FragmentApiUnavailable constructor.
   */
  public function __construct() {
    parent::__construct('Fragment API unavailable.');
  }

}
