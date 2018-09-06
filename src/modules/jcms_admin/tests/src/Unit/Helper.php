<?php

namespace Drupal\Tests\jcms_admin\Unit;

/**
 * Trait Helper.
 */
trait Helper {

  /**
   * Split strings into lines.
   */
  private function lines(array $lines, $breaks = 1) {
    return implode(str_repeat(PHP_EOL, $breaks), $lines);
  }

}
