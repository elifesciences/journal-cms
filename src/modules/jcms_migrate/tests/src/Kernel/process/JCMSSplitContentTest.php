<?php

namespace Drupal\Tests\jcms_migrate\Kernel\process;

use Drupal\filter\Entity\FilterFormat;
use Drupal\jcms_migrate\Plugin\migrate\process\JCMSSplitContent;
use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;

/**
 * Tests the split paragraphs process plugin.
 *
 * @coversDefaultClass \Drupal\jcms_migrate\Plugin\migrate\process\JCMSSplitContent
 * @group jcms_migrate
 */
class JCMSSplitContentTest extends KernelTestBase {

  /**
   * @var string
   */
  public static $allowedHtml = '<i> <sub> <sup> <span class> <del> <math> <a href> <b> <br> <table>';

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
            'allowed_html' => self::$allowedHtml,
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
  public function testTransform($html, $limit_types, $expected_result) {
    $split_content = $this->doTransform($html, $limit_types);
    $this->assertEquals($expected_result, $split_content);
  }

  public function transformDataProvider() {
    return [
      [
        '<p>Paragraph <b>1</b></p><p>Paragraph 2</p>',
        [
          'paragraph',
        ],
        [
          ['type' => 'paragraph', 'text' => 'Paragraph <b>1</b>'],
          ['type' => 'paragraph', 'text' => 'Paragraph 2'],
        ],
      ],
      [
        '<p>Paragraph</p><table><tr><td>Table cell</td></tr></table><ul><li>Unordered list item 1</li><li>Unordered list item 2</li></ul><ol><li>Ordered list item</li></ol><p>Paragraph</p>',
        [],
        [
          ['type' => 'paragraph', 'text' => 'Paragraph'],
          ['type' => 'table', 'html' => '<table><tr><td>Table cell</td></tr></table>'],
          ['type' => 'list', 'ordered' => FALSE, 'items' => ['Unordered list item 1', 'Unordered list item 2']],
          ['type' => 'list', 'ordered' => TRUE, 'items' => ['Ordered list item']],
          ['type' => 'paragraph', 'text' => 'Paragraph'],
        ],
      ],
      [
        '<p>Paragraph 1</p><p>&nbsp;</p><p>Paragraph 2</p>',
        [],
        [
          ['type' => 'paragraph', 'text' => 'Paragraph 1'],
          ['type' => 'paragraph', 'text' => 'Paragraph 2'],
        ],
      ],
      [
        '<p>Paragraph 1</p><img src="png/image.png" alt="Alt text" /><p>Paragraph 2</p>',
        [],
        [
          ['type' => 'paragraph', 'text' => 'Paragraph 1'],
          ['type' => 'image', 'image' => 'png/image.png', 'alt' => 'Alt text'],
          ['type' => 'paragraph', 'text' => 'Paragraph 2'],
        ],
      ],
      [
        '<div><a href="png/image.png"><img src="png/image.png"></a></div>',
        [],
        [
          ['type' => 'image', 'image' => 'png/image.png'],
        ],
      ],
      [
        "[caption align=left]<img src=\"https://journal-cms.dev/image.jpg\" /> Image Caption[/caption]",
        [],
        [
          ['type' => 'image', 'image' => 'https://journal-cms.dev/image.jpg', 'caption' => 'Image Caption'],
        ],
      ],
      [
        '<p>Paragraph 1</p><p><iframe allowfullscreen="" frameborder="0" height="315" src="//www.youtube.com/embed/Ykk0ELhUAxo" width="560"></iframe></p><p>Paragraph 2</p>',
        [],
        [
          ['type' => 'paragraph', 'text' => 'Paragraph 1'],
          ['type' => 'youtube', 'id' => 'Ykk0ELhUAxo', 'width' => '560', 'height' => '315'],
          ['type' => 'paragraph', 'text' => 'Paragraph 2'],
        ],
      ],
      [
        '<p>Paragraph 1</p><p><iframe allowfullscreen="" frameborder="0" height="315" src="//www.youtube.com/embed/Ykk0ELhUAxo" width="560"></iframe></p><p>Paragraph 2</p>',
        [
          'paragraph',
          'image',
        ],
        [
          ['type' => 'paragraph', 'text' => 'Paragraph 1'],
          ['type' => 'paragraph', 'text' => 'Paragraph 2'],
        ],
      ],
      [
        "We have turned on markdown as a format for our articles that you can request by setting your header to <code>text/plain</code>. We are not exactly sure how this might be useful, but we think it’s pretty cool anyway, and we hope it can be of benefit to those looking at markdown as a potential tool for the future of scholarly communication.\n\nHere is an example call:\n<pre><code>curl --header \"Accept:text/plain\"  \\n-L http://elife.elifesciences.org/content/2/e00334\n</code></pre>",
        [],
        [
          ['type' => 'paragraph', 'text' => 'We have turned on markdown as a format for our articles that you can request by setting your header to'],
          ['type' => 'code', 'code' => 'text/plain'],
          ['type' => 'paragraph', 'text' => '. We are not exactly sure how this might be useful, but we think it’s pretty cool anyway, and we hope it can be of benefit to those looking at markdown as a potential tool for the future of scholarly communication.'],
          ['type' => 'paragraph', 'text' => 'Here is an example call:'],
          ['type' => 'code', 'code' => 'curl --header "Accept:text/plain"  \\n-L http://elife.elifesciences.org/content/2/e00334'],
        ],
      ],
      [
        "<p>As part of our <a href=\"https://elifesciences.org/collections/plain-language-summaries\">&#x201C;Plain-language summaries of research&#x201D;</a> series, we have compiled a list of over 50 journals and other organizations that publish plain-language summaries of scientific research.</p>\n\n<p>Click <a href=\"https://docs.google.com/spreadsheets/d/1xqOMlzSI2rqxe6Eb3SZRRxmckXVXYACZAwbg3no4ZuI/edit#gid=0\">this link</a> to view the list and find out where you can read these summaries online.</p>\n\n<p>We need your help to keep this list up-to-date. To add a new organization to the&nbsp;list, or to update existing information, please contact us by email at <a href=\"mailto:features@elifesciences.org?subject=Update to plain-language summaries list\">features@elifesciences.org</a>.</p>\n\n<p>[caption]<img src=\"/sites/default/files/news/plain_language_summaries_v2.jpg\" style=\"height:352px; width:500px\" />Image credit: vividbiology.com[/caption]</p>",
        [],
        [
          ['type' => 'paragraph', 'text' => 'As part of our <a href="https://elifesciences.org/collections/plain-language-summaries">“Plain-language summaries of research”</a> series, we have compiled a list of over 50 journals and other organizations that publish plain-language summaries of scientific research.'],
          ['type' => 'paragraph', 'text' => 'Click <a href="https://docs.google.com/spreadsheets/d/1xqOMlzSI2rqxe6Eb3SZRRxmckXVXYACZAwbg3no4ZuI/edit#gid=0">this link</a> to view the list and find out where you can read these summaries online.'],
          ['type' => 'paragraph', 'text' => 'We need your help to keep this list up-to-date. To add a new organization to the list, or to update existing information, please contact us by email at <a href="mailto:features@elifesciences.org?subject=Update%20to%20plain-language%20summaries%20list">features@elifesciences.org</a>.'],
          ['type' => 'image', 'image' => '/sites/default/files/news/plain_language_summaries_v2.jpg', 'caption' => 'Image credit: vividbiology.com'],
        ],
      ],
    ];
  }

  /**
   * Transforms html into content blocks.
   *
   * @param array|string $value
   *   Source html.
   * @param array $limit_types
   *   Blocks to limit to in transfer, leave blank if no limit.
   *
   * @return array $actual
   *   The content blocks based on the source html.
   */
  protected function doTransform($value, $limit_types = []) {
    $row = new Row();
    $migration = $this->prophesize(MigrationInterface::class)->reveal();
    $executable = $this->prophesize(MigrateExecutableInterface::class)->reveal();

    $plugin = new TestJCMSSplitContent([], 'jcms_split_content', [], $migration);
    $plugin->setLimitTypes($limit_types);
    $actual = $plugin->transform($value, $executable, $row, 'destinationproperty');
    return $actual;
  }

}

class TestJCMSSplitContent extends JCMSSplitContent {

  /**
   * Set limit_types configuration.
   *
   * @param array $limit_types
   */
  public function setLimitTypes($limit_types) {
    $this->configuration['limit_types'] = $limit_types;
  }

}
