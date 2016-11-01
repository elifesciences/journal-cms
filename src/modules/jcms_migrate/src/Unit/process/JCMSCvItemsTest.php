<?php

namespace Drupal\Tests\jcms_migrate\Unit\process;

use Drupal\jcms_migrate\Plugin\migrate\process\JCMSCvItems;
use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;

/**
 * Tests the cv items process plugin.
 *
 * @coversDefaultClass \Drupal\jcms_migrate\Plugin\migrate\process\JCMSCvItems
 * @group jcms_migrate
 */
class JCMSCvItemsTest extends MigrateProcessTestCase {

  /**
   * @test
   * @covers ::prepareCvItems()
   * @dataProvider prepareCvItemsDataProvider
   * @group  journal-cms-tests
   */
  public function testPrepareCvItems($cv_dates, $cv_texts, $delimiter, $expected_result) {
    $plugin = new TestJCMSCvItems([], 'jcms_cv_items', []);
    $plugin->setDelimiter($delimiter);
    $cv_items = $plugin->prepareCvItems($cv_dates, $cv_texts);
    $this->assertEquals($expected_result, $cv_items);
  }

  public function prepareCvItemsDataProvider() {
    return [
      [
        '2013 - 2015|2015 - present',
        'CV item 1|CV item 2|CV item 3',
        '|',
        [
          ['date' => '2013 - 2015', 'text' => 'CV item 1'],
          ['date' => '2015 - present', 'text' => 'CV item 2'],
        ],
      ],
    ];
  }

}

class TestJCMSCvItems extends JCMSCvItems {
  public function __construct() {
  }

  /**
   * Set delimiter configuration.
   *
   * @param string $delimiter
   */
  public function setDelimiter($delimiter) {
    $this->configuration['delimiter'] = $delimiter;
  }

}
