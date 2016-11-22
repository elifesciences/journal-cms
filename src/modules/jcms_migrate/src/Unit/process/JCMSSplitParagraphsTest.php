<?php

namespace Drupal\Tests\jcms_migrate\Unit\process;

use Drupal\jcms_migrate\Plugin\migrate\process\JCMSSplitParagraphs;
use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;

/**
 * Tests the split paragraphs process plugin.
 *
 * @coversDefaultClass \Drupal\jcms_migrate\Plugin\migrate\process\JCMSSplitParagraphs
 * @group jcms_migrate
 */
class JCMSSplitParagraphsTest extends MigrateProcessTestCase {

  /**
   * @test
   * @covers ::transform()
   * @dataProvider transformDataProvider
   * @group  journal-cms-tests
   */
  public function testTransform($html, $strip_regex, $break_regex, $expected_result) {
    $plugin = new TestJCMSSplitParagraphs(array(), 'jcms_split_paragraphs', array());
    $plugin->setStripRegex($strip_regex, $break_regex);
    $split_paragraphs = $plugin->transform($html, $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertEquals($split_paragraphs, $expected_result);
  }

  public function transformDataProvider() {
    return [
      [
        '<p>Paragraph 1</p><p>Paragraph 2</p>',
        NULL,
        NULL,
        [
          ['type' => 'paragraph', 'text' => 'Paragraph 1'],
          ['type' => 'paragraph', 'text' => 'Paragraph 2'],
        ],
      ],
      [
        '<p>Paragraph 1</p><p>Paragraph 2</p><p>Keywords</p><p>Paragraph 4</p>',
        "",
        FALSE,
        [
          ['type' => 'paragraph', 'text' => 'Paragraph 1'],
          ['type' => 'paragraph', 'text' => 'Paragraph 2'],
          ['type' => 'paragraph', 'text' => 'Keywords'],
          ['type' => 'paragraph', 'text' => 'Paragraph 4'],
        ],
      ],
      [
        '<p>Paragraph 1</p><p>Paragraph 2</p><p>Keywords</p><p>Paragraph 4</p>',
        '(keywords|major subject area)',
        FALSE,
        [
          ['type' => 'paragraph', 'text' => 'Paragraph 1'],
          ['type' => 'paragraph', 'text' => 'Paragraph 2'],
          ['type' => 'paragraph', 'text' => 'Paragraph 4'],
        ],
      ],
      [
        '<p>Paragraph 1</p><p>Paragraph 2</p><p>Paragraph 3</p><p>Major subject area(s)</p><p>Paragraph 5</p><p>Keywords</p><p>Paragraph 7</p>',
        '(keywords|major subject area)',
        TRUE,
        [
          ['type' => 'paragraph', 'text' => 'Paragraph 1'],
          ['type' => 'paragraph', 'text' => 'Paragraph 2'],
          ['type' => 'paragraph', 'text' => 'Paragraph 3'],
        ],
      ],
    ];
  }

}

class TestJCMSSplitParagraphs extends JCMSSplitParagraphs {
  public function __construct() {
  }

  /**
   * Set strip_regex configuration.
   *
   * @param string|NULL $strip_regex
   * @param bool|NULL $break
   */
  public function setStripRegex($strip_regex, $break = FALSE) {
    if (is_null($strip_regex)) {
      if (isset($this->configuration['strip_regex'])) {
        unset($this->configuration['strip_regex']);
        unset($this->configuration['break_regex']);
      }
    }
    else {
      $this->configuration['strip_regex'] = $strip_regex;
      $this->configuration['break_regex'] = ($break === FALSE) ? FALSE : TRUE;
    }
  }

}
