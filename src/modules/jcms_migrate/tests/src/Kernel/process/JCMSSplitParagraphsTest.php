<?php

namespace Drupal\Tests\jcms_migrate\Kernel\process;

use Drupal\filter\Entity\FilterFormat;
use Drupal\jcms_migrate\Plugin\migrate\process\JCMSSplitParagraphs;
use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;

/**
 * Tests the split paragraphs process plugin.
 *
 * @coversDefaultClass \Drupal\jcms_migrate\Plugin\migrate\process\JCMSSplitParagraphs
 * @group jcms_migrate
 */
class JCMSSplitParagraphsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['jcms_migrate', 'filter'];

  protected function setUp() {
    parent::setUp();

    // Create basic HTML format.
    $basic_format = FilterFormat::create([
      'format' => 'basic_html',
      'name' => 'Basic HTML',
      'weight' => 1,
      'filters' => [
        'filter_html' => [
          'weight' => -10,
          'status' => 1,
          'settings' => [
            'allowed_html' => JCMSSplitContentTest::$allowedHtml,
          ],
        ],
      ],
    ]);
    $basic_format->save();
  }

  /**
   * @test
   * @covers ::transform
   * @dataProvider transformDataProvider
   * @group  journal-cms-tests
   */
  public function testTransform($html, $strip_regex, $break_regex, $expected_result) {
    $split_content = $this->doTransform($html, $strip_regex, $break_regex);
    $this->assertEquals($expected_result, $split_content);
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

  /**
   * Transforms html into content blocks.
   *
   * @param array|string $value
   *   Source html.
   * @param string|NULL $strip_regex
   *   Regex to strip from source html.
   * @param bool|NULL $break
   *   Set to TRUE if you wish to break after first match.
   *
   * @return array $actual
   *   The content blocks based on the source html.
   */
  protected function doTransform($value, $strip_regex, $break = FALSE) {
    $row = new Row();
    $migration = $this->prophesize(MigrationInterface::class)->reveal();
    $executable = $this->prophesize(MigrateExecutableInterface::class)->reveal();

    $plugin = new TestJCMSSplitParagraphs([], 'jcms_split_paragraphs', [], $migration);
    $plugin->setStripRegex($strip_regex, $break);
    $actual = $plugin->transform($value, $executable, $row, 'destinationproperty');
    return $actual;
  }

}

class TestJCMSSplitParagraphs extends JCMSSplitParagraphs {

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
