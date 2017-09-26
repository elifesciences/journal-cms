<?php

namespace Drupal\Tests\jcms_rest\Unit;

use Drupal\jcms_rest\Plugin\rest\resource\JobAdvertItemRestResource;
use Drupal\Tests\UnitTestCase;

/**
 * Class JCMSHtmlHelperTraitTest
 *
 * @package Drupal\Tests\jcms_rest\Unit
 */
class JobAdvertItemRestResourceTest extends UnitTestCase {

  public function providerSectionData()
  {
    return [
        [
            [
                'title' => 'section title',
                'content' => [],
            ],
            [
                'title' => 'section title',
                'content' => []
            ],
        ],
        [
            [
                'title' => 'section title',
                'content' => [
                    'The ideal candidate should have:',
                    [
                        'type' => 'list',
                        'prefix' => 'bullet',
                        'items' => [
                            'item 1',
                            'item 2'
                        ]
                    ],
                    'In addition:',
                    [
                        'type' => 'list',
                        'prefix' => 'bullet',
                        'items' => [
                            'item 1',
                            'item 2'
                        ],
                    ],
                ],
            ],
            [
                'title' => 'section title',
                'content' => [
                    [
                        'type' => 'paragraph',
                        'text' => 'The ideal candidate should have:'
                    ],
                    [
                        'type' => 'list',
                        'prefix' => 'bullet',
                        'items' => [
                            'item 1',
                            'item 2'
                        ]
                    ],
                    [
                        'type' => 'paragraph',
                        'text' => 'In addition:',
                    ],

                    [
                        'type' => 'list',
                        'prefix' => 'bullet',
                        'items' => [
                            'item 1',
                            'item 2'
                        ],
                    ],
                ],
            ],
        ],
    ];
  }

  public function providerSingleParagraph()
    {
        return [
            [
                '    The post is a permanent position, 37.5 hours per week, and offers a competitive basic salary and benefits. The post holder will be required to work at the eLife office in Cambridge (UK), and applicants must be able to demonstrate the right to live and work in the UK to be considered for the vacancy.    ',
                [
                    'text' => 'The post is a permanent position, 37.5 hours per week, and offers a competitive basic salary and benefits. The post holder will be required to work at the eLife office in Cambridge (UK), and applicants must be able to demonstrate the right to live and work in the UK to be considered for the vacancy.'
                ],
            ],
        ];
    }

  public function providerMultipleParagraphs()
    {
        return [
            [
                [
                    ' paragraph 1 ',
                    ' paragraph 2',
                    'paragraph 3 ',
                    'paragraph 4 ',
                ],
                [
                    [
                        'text' => 'paragraph 1'
                    ],
                    [
                        'text' => 'paragraph 2'
                    ],
                    [
                        'text' => 'paragraph 3'
                    ],
                    [
                        'text' => 'paragraph 4'
                    ],
                ],
            ]
        ];
    }

  /**
   * @test
   * @dataProvider providerSectionData
   * @covers \Drupal\jcms_rest\Plugin\rest\resource\JobAdvertItemRestResource::getFieldJsonAsSection
   * @group  journal-cms-tests
   */
  public function testGetFieldJsonAsSection($data, $expected) {
    $actual = JobAdvertItemRestResource::getFieldJsonAsSection($data['title'], $data['content']);
    $this->assertEquals('section', $actual['type']);
    $this->assertEquals($expected['title'], $actual['title']);
    $this->assertEquals($expected['content'], $actual['content']);
  }

  /**
   * @test
   * @dataProvider providerSingleParagraph
   * @covers \Drupal\jcms_rest\Plugin\rest\resource\JobAdvertItemRestResource::getFieldJsonAsParagraphs
   * @group  journal-cms-tests
   */
  public function testGetFieldJsonAsParagraphsReturnsSinlgeParagraph($singleParagraphData, $expected) {
    $actual = JobAdvertItemRestResource::getFieldJsonAsParagraphs($singleParagraphData);
    $this->assertEquals('paragraph', $actual['type']);
    $this->assertEquals($expected['text'], $actual['text']);
  }

  /**
   * @test
   * @dataProvider providerMultipleParagraphs
   * @covers \Drupal\jcms_rest\Plugin\rest\resource\JobAdvertItemRestResource::getFieldJsonAsParagraphs
   * @group  journal-cms-tests
   */
  public function testGetFieldJsonAsParagraphsReturnsMultipleParagraphs($multipleParagraphData, $expected) {
    $actual = JobAdvertItemRestResource::getFieldJsonAsParagraphs($multipleParagraphData);
    foreach($actual as $i => $item) {
        $this->assertEquals('paragraph', $item['type']);
        $this->assertEquals($expected[$i]['text'], $item['text']);
    }
  }

    /**
     * @test
     * @covers \Drupal\jcms_rest\Plugin\rest\resource\JobAdvertItemRestResource::getFieldLabel
     * @group  journal-cms-tests
     */
    public function testGetFieldLabel() {
        $mockNode = [
            'theFieldName'  => [

            ],
        ];
    }
}
