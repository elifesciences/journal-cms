<?php

namespace Drupal\jcms_rest;

/**
 * Helper method to check ID regex.
 */
trait JCMSCheckIdTrait {

  /**
   * Check ID matches expected regex pattern.
   */
  protected function checkId(string $id, string $type = NULL) : bool {
    if (
      (
        !is_null($type) &&
        (
          (in_array($type, ['article', 'digest', 'subject']) && preg_match('/^[a-z0-9-]+$/', $id)) ||
          ($type === 'podcast-episode' && preg_match('/^[1-9][0-9]*$/', $id)) ||
          ($type === 'annual-report' && preg_match('/^(?:20(?:1[2-9]|[2-9][0-9])|2[1-9][0-9][0-9]|[3-9][0-9][0-9][0-9])$/', $id))
        )
      ) ||
      preg_match('/^[0-9a-f]{8}$/', $id)
    ) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

}
