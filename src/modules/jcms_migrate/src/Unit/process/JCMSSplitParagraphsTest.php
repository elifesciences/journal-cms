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
  public function testTransform($html, $strip_keywords, $expected_result) {
    $plugin = new TestJCMSSplitParagraphs(array(), 'jcms_split_paragraphs', array());
    $plugin->setStripKeywords($strip_keywords);
    $split_paragraphs = $plugin->transform($html, $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertEquals($split_paragraphs, $expected_result);
  }

  public function transformDataProvider() {
    return [
      [
        '<p>Paragraph 1</p><p>Paragraph 2</p>',
        NULL,
        [
          ['type' => 'paragraph', 'text' => 'Paragraph 1'],
          ['type' => 'paragraph', 'text' => 'Paragraph 2'],
        ],
      ],
      [
        '<p>Paragraph 1</p><p>Paragraph 2</p><p>Keywords</p><p>Paragraph 4</p>',
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
        TRUE,
        [
          ['type' => 'paragraph', 'text' => 'Paragraph 1'],
          ['type' => 'paragraph', 'text' => 'Paragraph 2'],
        ],
      ],
      [
        '<p>Paragraph 1</p><p>Paragraph 2</p><p>Paragraph 3</p><p>Major subject area(s)</p><p>Paragraph 5</p><p>Keywords</p><p>Paragraph 7</p>',
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
   * Set strip_keywords configuration.
   *
   * @param NULL|bool $strip_keywords
   */
  public function setStripKeywords($strip_keywords = TRUE) {
    if (is_null($strip_keywords)) {
      if (isset($this->configuration['strip_keywords'])) {
        unset($this->configuration['strip_keywords']);
      }
    }
    elseif (!empty($strip_keywords)) {
      $this->configuration['strip_keywords'] = TRUE;
    }
    else {
      $this->configuration['strip_keywords'] = FALSE;
    }
  }

}
