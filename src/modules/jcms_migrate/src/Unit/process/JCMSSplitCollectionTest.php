<?php

use Drupal\jcms_migrate\Plugin\migrate\process\JCMSSplitCollectionContent;
use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;

/**
 * Tests the split paragraphs process plugin.
 *
 * @coversDefaultClass \Drupal\jcms_migrate\Plugin\migrate\process\JCMSSplitCollectionContent
 * @group jcms_migrate
 */
class JCMSSplitCollectionContentTest extends MigrateProcessTestCase {

  /**
   * @test
   * @covers ::transform()
   * @dataProvider transformDataProvider
   * @group  journal-cms-tests
   */
  public function testTransform($collection_content, $expected_result) {
    $plugin = new JCMSSplitCollectionContent([], 'jcms_split_collection_content', []);
    $split_collection_content = $plugin->transform($collection_content, $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertEquals($expected_result, $split_collection_content);
  }

  public function transformDataProvider() {
    return [
      [
        'article|06956||article|06100',
        [
          ['type' => 'article', 'source' => '06956'],
          ['type' => 'article', 'source' => '06100'],
        ],
      ],
      [
        'article|17393||blog_article|250467||blog_article|250461||blog_article|250437||article|09944||blog_article|250365||article|05614||article|04901||blog_article|250023||article|01633||article|01139||article|00676||article|00477',
        [
          ["type" => "article", "source" => "17393"],
          ["type" => "blog_article", "source" => "250467"],
          ["type" => "blog_article", "source" => "250461"],
          ["type" => "blog_article", "source" => "250437"],
          ["type" => "article", "source" => "09944"],
          ["type" => "blog_article", "source" => "250365"],
          ["type" => "article", "source" => "05614"],
          ["type" => "article", "source" => "04901"],
          ["type" => "blog_article", "source" => "250023"],
          ["type" => "article", "source" => "01633"],
          ["type" => "article", "source" => "01139"],
          ["type" => "article", "source" => "00676"],
          ["type" => "article", "source" => "00477"],
        ],
      ],
    ];
  }

}
