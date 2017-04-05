<?php

namespace Drupal\Tests\jcms_migrate\Unit\process;

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
   * @covers ::imgStyleDimensions
   * @dataProvider imgStyleDimensionsDataProvider
   * @param $string
   * @param $expected_result
   */
  public function testImgStyleDimensions($string, $expected_result) {
    $plugin = new JCMSSplitContent([], 'jcms_split_content', []);
    $img_style_dimensions = $plugin->imgStyleDimensions($string);
    $this->assertEquals($expected_result, $img_style_dimensions);
  }

  public function imgStyleDimensionsDataProvider() {
    return [
      [
        "<img alt=\"Buz Barstow\" src=\"https://cdn.elifesciences.org/images/news/Buz_Barstow.jpg\" style=\"height:125px; width:100px\" />",
        "<img alt=\"Buz Barstow\" src=\"https://cdn.elifesciences.org/images/news/Buz_Barstow.jpg\" width=\"100\" height=\"125\" style=\"height:125px; width:100px\" />",
      ],
      [
        "<img alt=\"Buz Barstow\" width=\"110\" src=\"https://cdn.elifesciences.org/images/news/Buz_Barstow.jpg\" style=\"height:125px; width:100px\" />",
        "<img alt=\"Buz Barstow\" width=\"110\" src=\"https://cdn.elifesciences.org/images/news/Buz_Barstow.jpg\" height=\"125\" style=\"height:125px; width:100px\" />",
      ],
    ];
  }

  /**
   * @test
   * @covers ::captionConvert
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
   * @covers ::codeConvert
   * @dataProvider codeConvertDataProvider
   * @param $string
   * @param $expected_result
   */
  public function testCodeConvert($string, $expected_result) {
    $plugin = new JCMSSplitContent([], 'jcms_split_content', []);
    $caption_convert = $plugin->codeConvert($string);
    $this->assertEquals($expected_result, $caption_convert);
  }

  public function codeConvertDataProvider() {
    return [
      [
        "<code>Some code\n\nto display</code>",
        "<code>U29tZSBjb2RlCgp0byBkaXNwbGF5</code>",
      ],
    ];
  }

  /**
   * @test
   * @covers ::nl2p
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
   * @covers ::youtubeID
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
   * @covers ::youtubeConvert
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
