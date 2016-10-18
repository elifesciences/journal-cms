<?php

namespace Drupal\Tests\jcms_migrate\Unit\process;

use Drupal\jcms_migrate\Plugin\migrate\process\JCMSSplitParagraphs;
use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;

/**
 * Tests the split paragraphs process plugin.
 *
 * @group jcms_migrate
 */
class JCMSSplitParagraphsTest extends MigrateProcessTestCase {

  /**
   * @test
   * @covers \Drupal\jcms_migrate\Plugin\migrate\process\JCMSSplitParagraphs::transform()
   * @dataProvider getTransformDataProvider
   * @group  journal-cms-tests
   */
  public function testTransform($html, $expected_result) {
    $plugin = new JCMSSplitParagraphs(array(), 'jcms_split_paragraphs', array());
    $split_paragraphs = $plugin->transform($html, $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame($split_paragraphs, $expected_result);
  }

  public function getTransformDataProvider() {
    return [
      [
        '<p>Paragraph 1</p><p>Paragraph 2</p>',
        [
          ['type' => 'paragraph', 'text' => 'Paragraph 1'],
          ['type' => 'paragraph', 'text' => 'Paragraph 2'],
        ],
      ],
    ];
  }

}
