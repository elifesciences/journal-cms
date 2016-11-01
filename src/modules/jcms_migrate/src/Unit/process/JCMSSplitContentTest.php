<?php

namespace Drupal\Tests\jcms_migrate\Unit\process {

  use Drupal\jcms_migrate\Plugin\migrate\process\JCMSSplitContent;
  use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;

  /**
   * Tests the split paragraphs process plugin.
   *
   * @coversDefaultClass \Drupal\jcms_migrate\Plugin\migrate\process\JCMSSplitContent
   * @group jcms_migrate
   */
  class JCMSSplitContentTest extends MigrateProcessTestCase {

    /**
     * @test
     * @covers ::transform()
     * @dataProvider transformDataProvider
     * @group  journal-cms-tests
     */
    public function testTransform($html, $limit_types, $expected_result) {
      $plugin = new TestJCMSSplitContent([], 'jcms_split_content', []);
      $plugin->setLimitTypes($limit_types);
      $split_content = $plugin->transform($html, $this->migrateExecutable, $this->row, 'destinationproperty');
      $this->assertEquals($expected_result, $split_content);
    }

    public function transformDataProvider() {
      return [
        [
          '<p>Paragraph 1</p><p>Paragraph 2</p>',
          [],
          [
            ['type' => 'paragraph', 'text' => 'Paragraph 1'],
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
        // @todo - elife - nlisgo - add test for entity_id 249171 field_data_field_elife_n_text
      ];
    }

    /**
     * @test
     * @covers ::captionConvert()
     * @dataProvider captionConvertDataProvider
     * @param $string
     * @param $expected_result
     */
    public function testCaptionConvert($string, $expected_result) {
      $plugin = new JCMSSplitContent([], 'jcms_split_content', []);
      $caption_convert = $plugin->captionConvert($string);
      $this->assertEquals($expected_result, $caption_convert);
    }

    public function captionConvertDataProvider() {
      return [
        [
          "[caption align=left]<img src=\"https://journal-cms.dev/image.jpg\" /> Image Caption[/caption]",
          "<img src=\"https://journal-cms.dev/image.jpg\" caption=\"Image Caption\"/>",
        ],
        [
          "<p>[caption]<img alt=\"Monica Alandete-Saez\" src=\"/sites/default/files/monica_alandete-saez.jpg\" style=\"height:427px; width:320px\" title=\"Monica Alandete-Saez. Image credit: Mily Ron\">Monica Alandete-Saez. Image credit: Mily Ron[/caption]</p>",
          "<p><img alt=\"Monica Alandete-Saez\" src=\"/sites/default/files/monica_alandete-saez.jpg\" style=\"height:427px; width:320px\" title=\"Monica Alandete-Saez. Image credit: Mily Ron\" caption=\"Monica Alandete-Saez. Image credit: Mily Ron\"></p>",
        ],
      ];
    }

    /**
     * @test
     * @covers ::nl2p()
     * @dataProvider nl2pDataProvider
     * @group  journal-cms-tests
     */
    public function testNl2p($html, $expected_result) {
      $plugin = new JCMSSplitContent([], 'jcms_split_content', []);
      $nl2p = $plugin->nl2p($html);
      $this->assertEquals($expected_result, $nl2p);
    }

    public function nl2pDataProvider() {
      return [
        [
          "<div><img src=\"https://journal-cms.dev/image.jpg\" />The Amboseli Baboon Research Project (ABRP) is a long-term study of yellow baboons, <em>Papio cynocephalus</em>, in Kenya, just north of Mt Kilimanjaro.</div>",
          "<img src=\"https://journal-cms.dev/image.jpg\" /><p>The Amboseli Baboon Research Project (ABRP) is a long-term study of yellow baboons, <em>Papio cynocephalus</em>, in Kenya, just north of Mt Kilimanjaro.</p>",
        ],
      ];
    }

    /**
     * @test
     * @covers ::youtubeID()
     * @dataProvider youtubeIDDataProvider
     * @group  journal-cms-tests
     */
    public function testYoutubeID($url, $expected_result) {
      $plugin = new JCMSSplitContent([], 'jcms_split_content', []);
      $youtube_id = $plugin->youtubeID($url);
      $this->assertEquals($expected_result, $youtube_id);
    }

    public function youtubeIDDataProvider() {
      return [
        [
          "https://www.youtube.com/embed/Y8Bcr2KTa9o",
          "Y8Bcr2KTa9o",
        ],
        [
          "//www.youtube.com/embed/Y8Bcr2KTa9o",
          "Y8Bcr2KTa9o",
        ],
        [
          "//www.youtube.com/watch?v=Y8Bcr2KTa9o",
          "Y8Bcr2KTa9o",
        ],
        [
          "https://player.vimeo.com/video/67254579",
          NULL,
        ],
        [
          "youtube.com/v/Y8Bcr2KTa9o",
          "Y8Bcr2KTa9o",
        ],
        [
          "youtube.com/vi/Y8Bcr2KTa9o",
          "Y8Bcr2KTa9o",
        ],
        [
          "youtube.com/?v=Y8Bcr2KTa9o",
          "Y8Bcr2KTa9o",
        ],
        [
          "youtube.com/?vi=Y8Bcr2KTa9o",
          "Y8Bcr2KTa9o",
        ],
        [
          "youtube.com/watch?v=Y8Bcr2KTa9o",
          "Y8Bcr2KTa9o",
        ],
        [
          "youtube.com/watch?vi=Y8Bcr2KTa9o",
          "Y8Bcr2KTa9o",
        ],
        [
          "youtu.be/Y8Bcr2KTa9o",
          "Y8Bcr2KTa9o",
        ],
        [
          "youtube.com/embed/Y8Bcr2KTa9o",
          "Y8Bcr2KTa9o",
        ],
        [
          "http://youtube.com/v/Y8Bcr2KTa9o",
          "Y8Bcr2KTa9o",
        ],
        [
          "http://www.youtube.com/v/Y8Bcr2KTa9o",
          "Y8Bcr2KTa9o",
        ],
        [
          "https://www.youtube.com/v/Y8Bcr2KTa9o",
          "Y8Bcr2KTa9o",
        ],
        [
          "youtube.com/watch?v=Y8Bcr2KTa9o&wtv=wtv",
          "Y8Bcr2KTa9o",
        ],
        [
          "http://www.youtube.com/watch?dev=inprogress&v=Y8Bcr2KTa9o&feature=related",
          "Y8Bcr2KTa9o",
        ],
        [
          "https://m.youtube.com/watch?v=Y8Bcr2KTa9o",
          "Y8Bcr2KTa9o",
        ],
      ];
    }

    /**
     * @test
     * @covers ::youtubeConvert()
     * @dataProvider youtubeConvertDataProvider
     * @group  journal-cms-tests
     */
    public function testYoutubeConvert($html, $expected_result) {
      $plugin = new JCMSSplitContent([], 'jcms_split_content', []);
      $convert_html = $plugin->youtubeConvert($html);
      $this->assertEquals($expected_result, $convert_html);
    }

    public function youtubeConvertDataProvider() {
      return [
        [
          "<p><iframe allowfullscreen=\"\" frameborder=\"0\" height=\"315\" src=\"//www.youtube.com/embed/Ykk0ELhUAxo\" width=\"560\"></iframe></p>",
          "<p><youtube id=\"Ykk0ELhUAxo\" width=\"560\" height=\"315\"/></p>",
        ],
      ];
    }

  }

  class TestJCMSSplitContent extends JCMSSplitContent {
    public function __construct() {
    }

    /**
     * Set limit_types configuration.
     *
     * @param array $limit_types
     */
    public function setLimitTypes($limit_types) {
      $this->configuration['limit_types'] = $limit_types;
    }

  }
}

namespace {
  if (!function_exists('check_markup')) {
    function check_markup($html, $format_id = 'basic_html') {
      return $html;
    }
  }
}
