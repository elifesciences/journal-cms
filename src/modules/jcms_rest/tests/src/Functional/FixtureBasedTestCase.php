<?php

namespace Drupal\Tests\jcms_rest\Functional;

use Drupal\Tests\UnitTestCase;

abstract class FixtureBasedTestCase extends UnitTestCase {

  protected static $contentGenerated = FALSE;

  public static function setUpBeforeClass()
  {
    parent::setUpBeforeClass();
    // Generate content once.
    if (!self::$contentGenerated) {
      self::$contentGenerated = TRUE;
      $projectRoot = realpath(__DIR__ . '/../../../../../..');
      $script = $projectRoot . '/scripts/generate_content.sh';
      if (!file_exists($script)) {
        throw new RuntimeException("File $script does not exist");
      }
      $logFile = '/tmp/generate_content.log';
      exec("$script >$logFile 2>&1", $output, $exitCode);
      if ($exitCode != 0) {
        throw new RuntimeException("$script failed. Check log file $logFile");
      }
    }
  }
}
