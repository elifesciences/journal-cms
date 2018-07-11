<?php

namespace Drupal\Tests\jcms_admin\Unit;

use Drupal\jcms_admin\JsonHtmlDeserializer;
use eLife\ApiSdk\Model\Model;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Tests for JsonHtmlDeserializer.
 */
class JsonHtmlDeserializerTest extends TestCase {
  /**
   * Denormalizer.
   *
   * @var \Drupal\jcms_admin\JsonHtmlDeserializer
   */
  private $denormalizer;

  /**
   * Setup.
   *
   * @before
   */
  protected function setUpDenormalizer() {
    $this->denormalizer = new JsonHtmlDeserializer();
  }

  /**
   * Provider.
   */
  public function denormalizeProvider() : array {
    return [
      'minimal' => [
        [
          'content' => [],
        ],
        '',
      ],
      'single section' => [
        [
          'content' => [
            [
              'type' => 'section',
              'title' => 'Section heading',
              'content' => [
                [
                  'type' => 'paragraph',
                  'text' => 'Single paragraph',
                ],
              ],
            ],
          ],
        ],
        $this->lines([
          '<h1>Section heading</h1>',
          '<p>Single paragraph</p>',
        ]),
      ],
      'questions' => [
        [
          'content' => [
            [
              'type' => 'question',
              'question' => 'Do you like my question?',
              'answer' => [
                [
                  'type' => 'paragraph',
                  'text' => 'This is an answer to the question.',
                ],
                [
                  'type' => 'paragraph',
                  'text' => 'This is an extended answer.',
                ],
              ],
            ],
            [
              'type' => 'quote',
              'text' => [
                [
                  'type' => 'paragraph',
                  'text' => 'Quote',
                ],
              ],
            ],
            [
              'type' => 'paragraph',
              'text' => 'This is not an answer.',
            ],
            [
              'type' => 'question',
              'question' => 'Next question?',
              'answer' => [
                [
                  'type' => 'paragraph',
                  'text' => 'OK!',
                ],
              ],
            ],
          ],
        ],
        $this->lines([
          '<h1>Do you like my question?</h1>',
          '<p>This is an answer to the question.</p>',
          '<p>This is an extended answer.</p>',
          '<blockquote>Quote</blockquote>',
          '<p>This is not an answer.</p>',
          '<h1>Next question?</h1>',
          '<p>OK!</p>',
        ]),
      ],
      'span soup' => [
        [
          'content' => [
            [
              'type' => 'paragraph',
              'text' => '<span><span><span><span><span>Lotte Meteyard has been a lecturer at the University of Reading since 2010. She did her PhD from 2004 to 2007 at University College London, followed by a postdoc in 2008 at the Cognition and Brain Sciences Unit, Cambridge. From 2008 to 2010 she </span></span></span></span></span><span><span><span><span><span>retrained as a speech and language therapist. She got m</span></span></span></span></span>arried in 2012 and had her first child in 2015.',
            ],
          ],
        ],
        $this->lines([
          '<p>Lotte Meteyard has been a lecturer at the University of Reading since 2010. She did her PhD from 2004 to 2007 at University College London, followed by a postdoc in 2008 at the Cognition and Brain Sciences Unit, Cambridge. From 2008 to 2010 she retrained as a speech and language therapist. She got married in 2012 and had her first child in 2015.</p>',
        ]),
      ],
      'single paragraph' => [
        [
          'content' => [
            [
              'type' => 'paragraph',
              'text' => '<strong>Single</strong> paragraph',
            ],
          ],
        ],
        '<p><strong>Single</strong> paragraph</p>',
      ],
      'single table' => [
        [
          'content' => [
            [
              'type' => 'table',
              'tables' => [
                '<table><tr><td>Cell one</td></tr></table>',
              ],
            ],
          ],
        ],
        '<table><tr><td>Cell one</td></tr></table>',
      ],
      'simple image' => [
        [
          'content' => [
            [
              'type' => 'image',
              'image' => [
                'uri' => 'https://iiif.elifesciences.org/journal-cms:editor-images/image-20180427145110-1.jpeg',
                'source' => [
                  'mediaType' => 'image/jpeg',
                  'uri' => 'https://iiif.elifesciences.org/journal-cms:editor-images/image-20180427145110-1.jpeg/full/full/0/default.jpg',
                  'filename' => 'image-20180427145110-1.jpeg',
                ],
                'size' => [
                  'width' => 2000,
                  'height' => 2000,
                ],
                'focalPoint' => [
                  'x' => 50,
                  'y' => 50,
                ],
                'attribution' => [
                  'Image attribution',
                ],
              ],
              'title' => 'A nice picture of a field. Courtesy of <a href="https://www.pexels.com/photo/biology-blur-close-up-dragonflies-287361/">Pexels</a>.',
            ],
            [
              'type' => 'paragraph',
              'text' => 'Trailing paragraph',
            ],
          ],
        ],
        $this->lines([
          '<figure class="image align-center"><img alt="" data-fid="123" data-uuid="UUID" src="/sites/default/files/iiif/editor-images/image-20180427145110-1.jpeg" width="2000" height="2000" />',
          '<figcaption>A nice picture of a field. Courtesy of <a href="https://www.pexels.com/photo/biology-blur-close-up-dragonflies-287361/">Pexels</a>. Image attribution.</figcaption>',
          '</figure>',
          '<p>Trailing paragraph</p>',
        ]),
        [
          'fids' => [
            'public://iiif/editor-images/image-20180427145110-1.jpeg' => [
              'fid' => 123,
              'src' => '/sites/default/files/iiif/editor-images/image-20180427145110-1.jpeg',
            ],
          ],
        ],
      ],
      'figure' => [
                [
                  'content' => [
            [
              'type' => 'figure',
              'assets' => [
                [
                  'id' => 'image-1',
                  'type' => 'image',
                  'image' => [
                    'uri' => 'https://prod--iiif.elifesciences.org/journal-cms:content/2017-08/2017_09_27_ecr_webinar_panellists_long.png',
                    'source' => [
                      'mediaType' => 'image/jpeg',
                      'uri' => 'https://prod--iiif.elifesciences.org/journal-cms:content/2017-08/2017_09_27_ecr_webinar_panellists_long.png/full/full/0/default.jpg',
                      'filename' => '2017_09_27_ecr_webinar_panellists_long.jpg',
                    ],
                    'size' => [
                      'width' => 4396,
                      'height' => 1397,
                    ],
                    'focalPoint' => [
                      'x' => 50,
                      'y' => 50,
                    ],
                    'attribution' => [
                      'Image of Kevin Marsh is from https://www.ndm.ox.ac.uk/principal-investigators/researcher/kevin-marsh, image of Simon Kay is from http://www.malariaconsortium.org/board/trustees/47/simon-kay, image of Catherine Kyobutungi is from http://aphrc.org/post/team/catherine-kyobutungi',
                    ],
                  ],
                  'label' => 'Building Connections and Developing Research in Sub-Saharan Africa Panellists',
                  'title' => 'Building Connections and Developing Research in Sub-Saharan Africa chair and panellists',
                ],
              ],
            ],
                  ],
                ],
        $this->lines([
          '<figure class="image align-center"><img alt="" data-fid="123" data-uuid="UUID" src="/sites/default/files/iiif/content/2017-08/2017_09_27_ecr_webinar_panellists_long.png" width="4396" height="1397" />',
          '<figcaption>Building Connections and Developing Research in Sub-Saharan Africa Panellists. Building Connections and Developing Research in Sub-Saharan Africa chair and panellists. Image of Kevin Marsh is from https://www.ndm.ox.ac.uk/principal-investigators/researcher/kevin-marsh, image of Simon Kay is from http://www.malariaconsortium.org/board/trustees/47/simon-kay, image of Catherine Kyobutungi is from http://aphrc.org/post/team/catherine-kyobutungi.</figcaption>',
          '</figure>',
        ]),
        [
          'fids' => [
            'public://iiif/content/2017-08/2017_09_27_ecr_webinar_panellists_long.png' => [
              'fid' => 123,
              'src' => '/sites/default/files/iiif/content/2017-08/2017_09_27_ecr_webinar_panellists_long.png',
            ],
          ],
        ],
      ],
      'inline image' => [
        [
          'content' => [
            [
              'type' => 'image',
              'image' => [
                'uri' => 'https://iiif.elifesciences.org/journal-cms:editor-images/image-20180427145110-1.jpeg',
                'alt' => '',
                'source' => [
                  'mediaType' => 'image/jpeg',
                  'uri' => 'https://iiif.elifesciences.org/journal-cms:editor-images/image-20180427145110-1.jpeg/full/full/0/default.jpg',
                  'filename' => 'image-20180427145110-1.jpeg',
                ],
                'size' => [
                  'width' => 2000,
                  'height' => 2000,
                ],
                'focalPoint' => [
                  'x' => 50,
                  'y' => 50,
                ],
              ],
              'inline' => TRUE,
            ],
          ],
        ],
        $this->lines([
          '<figure class="image align-left"><img alt="" data-fid="123" data-uuid="UUID" src="/sites/default/files/iiif/editor-images/image-20180427145110-1.jpeg" width="2000" height="2000" />',
          '</figure>',
        ]),
        [
          'fids' => [
            'public://iiif/editor-images/image-20180427145110-1.jpeg' => [
              'fid' => 123,
              'src' => '/sites/default/files/iiif/editor-images/image-20180427145110-1.jpeg',
            ],
          ],
        ],
      ],
      'multiple tables' => [
        [
          'content' => [
            [
              'type' => 'table',
              'tables' => [
                '<table><tr><td>Cell one</td></tr></table>',
              ],
            ],
            [
              'type' => 'table',
              'tables' => [
                '<table><tr><td>Cell two</td></tr></table>',
              ],
            ],
          ],
        ],
        $this->lines([
          '<table><tr><td>Cell one</td></tr></table>',
          '<table><tr><td>Cell two</td></tr></table>',
        ]),
      ],
      'simple list' => [
        [
          'content' => [
            [
              'type' => 'paragraph',
              'text' => 'Nested list:',
            ],
            [
              'type' => 'list',
              'prefix' => 'bullet',
              'items' => [
                'Item 1',
                'Item 2',
                [
                  [
                    'type' => 'list',
                    'prefix' => 'bullet',
                    'items' => [
                      'Item 2.1',
                      [
                        [
                          'type' => 'list',
                          'prefix' => 'number',
                          'items' => [
                            'Item 2.1.1',
                          ],
                        ],
                      ],
                    ],
                  ],
                ],
              ],
            ],
          ],
        ],
        $this->lines([
          '<p>Nested list:</p>',
          '<ul>',
          '<li>Item 1</li>',
          '<li>Item 2<ul><li>Item 2.1<ol><li>Item 2.1.1</li></ol></li></ul></li>',
          '</ul>',
        ]),
      ],
      'single blockquote' => [
        [
          'content' => [
            [
              'type' => 'quote',
              'text' => [
                [
                  'type' => 'paragraph',
                  'text' => 'Blockquote line 1',
                ],
              ],
            ],
          ],
        ],
        '<blockquote>Blockquote line 1</blockquote>',
      ],
      'simple code sample' => [
        [
          'content' => [
            [
              'type' => 'code',
              'code' => $this->lines([
                'Code sample line 1',
                'Code sample line 2',
              ], 2),
            ],
          ],
        ],
        $this->lines([
          '<code>',
          'Code sample line 1' . PHP_EOL,
          'Code sample line 2',
          '</code>',
        ]),
      ],
      'preserve hierarchy' => [
        [
          'content' => [
            [
              'type' => 'paragraph',
              'text' => 'Paragraph 1.',
            ],
            [
              'type' => 'section',
              'title' => 'Section 1',
              'content' => [
                [
                  'type' => 'paragraph',
                  'text' => 'Paragraph 1 in Section 1.',
                ],
                [
                  'type' => 'paragraph',
                  'text' => 'Paragraph 2 in Section 1.',
                ],
                [
                  'type' => 'paragraph',
                  'text' => 'Paragraph 3 in Section 1.',
                ],
                [
                  'type' => 'section',
                  'title' => 'Section 1.1',
                  'content' => [
                    [
                      'type' => 'paragraph',
                      'text' => 'Paragraph 1 in Section 1.1.',
                    ],
                    [
                      'type' => 'quote',
                      'text' => [
                        [
                          'type' => 'paragraph',
                          'text' => 'Blockquote 1 in Section 1.1.',
                        ],
                      ],
                    ],
                    [
                      'type' => 'paragraph',
                      'text' => 'Paragraph 2 in Section 1.1.',
                    ],
                    [
                      'type' => 'code',
                      'code' => $this->lines([
                        'Code sample 1 line 1 in Section 1.1.',
                        'Code sample 1 line 2 in Section 1.1.',
                      ], 2),
                    ],
                  ],
                ],
                [
                  'type' => 'section',
                  'title' => 'Section 1.2',
                  'content' => [
                    [
                      'type' => 'paragraph',
                      'text' => 'Paragraph 1 in Section 1.2.',
                    ],
                  ],
                ],
              ],
            ],
            [
              'type' => 'section',
              'title' => 'Section 2',
              'content' => [
                [
                  'type' => 'paragraph',
                  'text' => 'Paragraph 1 in Section 2.',
                ],
                [
                  'type' => 'table',
                  'tables' => [
                    '<table><tr><td>Table 1 in Section 2.</td></tr></table>',
                  ],
                ],
                [
                  'type' => 'paragraph',
                  'text' => 'Paragraph 2 in Section 2.',
                ],
              ],
            ],
          ],
        ],
        $this->lines([
          '<p>Paragraph 1.</p>',
          '<h1>Section 1</h1>',
          '<p>Paragraph 1 in Section 1.</p>',
          '<p>Paragraph 2 in Section 1.</p>',
          '<p>Paragraph 3 in Section 1.</p>',
          '<h2>Section 1.1</h2>',
          '<p>Paragraph 1 in Section 1.1.</p>',
          '<blockquote>Blockquote 1 in Section 1.1.</blockquote>',
          '<p>Paragraph 2 in Section 1.1.</p>',
          '<code>' . PHP_EOL . 'Code sample 1 line 1 in Section 1.1.' . PHP_EOL,
          'Code sample 1 line 2 in Section 1.1.' . PHP_EOL . '</code>',
          '<h2>Section 1.2</h2>',
          '<p>Paragraph 1 in Section 1.2.</p>',
          '<h1>Section 2</h1>',
          '<p>Paragraph 1 in Section 2.</p>',
          '<table><tr><td>Table 1 in Section 2.</td></tr></table>',
          '<p>Paragraph 2 in Section 2.</p>',
        ]),
      ],
      'cv items' => [
        [
          'interviewee' => [
            'name' => [
              'preferred' => 'Adam Brooks',
            ],
            'cv' => [
              [
                'date' => '2017 - present',
                'text' => 'Current position',
              ],
              [
                'date' => '2015 - 2017',
                'text' => 'Previous position',
              ],
            ],
          ],
          'content' => [
            [
              'type' => 'paragraph',
              'text' => '<strong>Single</strong> paragraph',
            ],
          ],
        ],
        $this->lines([
          '<p><strong>Single</strong> paragraph</p>',
          '<h1>Adam Brooks CV</h1>',
          '<ul>',
          '<li><b>2017 - present</b>: Current position</li>',
          '<li><b>2015 - 2017</b>: Previous position</li>',
          '</ul>',
        ]),
      ],
      'single button' => [
        [
          'content' => [
            [
              'type' => 'button',
              'text' => 'Button text',
              'uri' => 'http://example.com',
            ],
          ],
        ],
        '<elifebutton class="elife-button--default" data-href="http://example.com">Button text</elifebutton>',
      ],
      'single youtube' => [
        [
          'content' => [
            [
              'type' => 'youtube',
              'id' => 'oyBX9l9KzU8',
              'width' => '16',
              'height' => '9',
            ],
          ],
        ],
        '<oembed>https://www.youtube.com/watch?v=oyBX9l9KzU8</oembed>',
      ],
      'list soup' => [
        [
          'content' => [
            [
              'type' => 'section',
              'title' => 'Planning the user study',
              'content' => [
                [
                  'type' => 'paragraph',
                  'text' => 'We had a made lot of decisions for the redesign of the new eLife website internally, and we now needed to see how well they fared when put in front of real users.',
                ],
                [
                  'type' => 'paragraph',
                  'text' => '<b>High-level goals</b>',
                ],
                [
                  'type' => 'paragraph',
                  'text' => '<ul><li>We wanted to know how users would feel about such dramatic visual change to the eLife website compared to the experience they were used to.</li><li>We wanted to make sure users would still be able to find their way around a restructured website.</li><li>We wanted to know if this redesign would improve the article reading experience for our visitors.</li><li>We wanted to see if we had created a great mobile experience for our users.</li></ul><b>Turning goals into questions</b>',
                ],
                [
                  'type' => 'paragraph',
                  'text' => 'In order to achieve our goals, we needed to formulate some key questions. Typically we would take a goal and develop a set of questions that could be answered through direct observation of our users’ interaction with our design prototypes.',
                ],
                [
                  'type' => 'paragraph',
                  'text' => 'An example of turning a goal into a question might be:',
                ],
                [
                  'type' => 'paragraph',
                  'text' => '<ul dir=\"ltr\"><li><b>Goal: </b>We wanted to know how users would feel about such dramatic visual change to the eLife website compared to the experience they were used to.</li><li><b>Question:</b> How do users respond when they are presented with the prototype of the redesigned eLife website?</li></ul><b>Developing tasks to answer questions</b>',
                ],
                [
                  'type' => 'paragraph',
                  'text' => 'Rather than directly asking our users questions, we developed a script of tasks allowing them to express their thoughts and feelings in a more natural way, by using the<a href=\"https://en.wikipedia.org/wiki/Think_aloud_protocol\"> think aloud protocol</a>. This also helped us to uncover unexpected needs and test the usability of our designs in a non-leading way.',
                ],
                [
                  'type' => 'paragraph',
                  'text' => 'An example of turning one of the above questions into a task would be:',
                ],
                [
                  'type' => 'list',
                  'prefix' => 'bullet',
                  'items' => [
                    '<b>Question:</b> How do users respond when they are presented with the prototype of the redesigned eLife website?',
                    '<b>Task:</b> "Can you describe the webpage on the screen in front of you?"',
                  ],
                ],
              ],
            ],
          ],
        ],
        $this->lines([
          '<h1>Planning the user study</h1>',
          '<p>We had a made lot of decisions for the redesign of the new eLife website internally, and we now needed to see how well they fared when put in front of real users.</p>',
          '<p><b>High-level goals</b></p>',
          '<ul>',
          '<li>We wanted to know how users would feel about such dramatic visual change to the eLife website compared to the experience they were used to.</li>',
          '<li>We wanted to make sure users would still be able to find their way around a restructured website.</li>',
          '<li>We wanted to know if this redesign would improve the article reading experience for our visitors.</li>',
          '<li>We wanted to see if we had created a great mobile experience for our users.</li>',
          '</ul>',
          '<p><b>Turning goals into questions</b></p>',
          '<p>In order to achieve our goals, we needed to formulate some key questions. Typically we would take a goal and develop a set of questions that could be answered through direct observation of our users’ interaction with our design prototypes.</p>',
          '<p>An example of turning a goal into a question might be:</p>',
          '<ul>',
          '<li><b>Goal: </b>We wanted to know how users would feel about such dramatic visual change to the eLife website compared to the experience they were used to.</li>',
          '<li><b>Question:</b> How do users respond when they are presented with the prototype of the redesigned eLife website?</li>',
          '</ul>',
          '<p><b>Developing tasks to answer questions</b></p>',
          '<p>Rather than directly asking our users questions, we developed a script of tasks allowing them to express their thoughts and feelings in a more natural way, by using the<a href=\"https://en.wikipedia.org/wiki/Think_aloud_protocol\"> think aloud protocol</a>. This also helped us to uncover unexpected needs and test the usability of our designs in a non-leading way.</p>',
          '<p>An example of turning one of the above questions into a task would be:</p>',
          '<ul>',
          '<li><b>Question:</b> How do users respond when they are presented with the prototype of the redesigned eLife website?</li>',
          '<li><b>Task:</b> "Can you describe the webpage on the screen in front of you?"</li>',
          '</ul>',
        ]),
      ],
    ];
  }

  /**
   * Verify that denormalizer detected.
   *
   * @test
   */
  public function itIsDenormalizer() {
    $this->assertInstanceOf(DenormalizerInterface::class, $this->denormalizer);
  }

  /**
   * Verify that it can be denormalized.
   *
   * @test
   * @dataProvider canDenormalizeProvider
   */
  public function itCanDenormalizeSupportedTypes($data, $format, array $context, bool $expected) {
    $this->assertSame($expected, $this->denormalizer->supportsDenormalization($data, $format, $context));
  }

  /**
   * Provider.
   */
  public function canDenormalizeProvider() : array {
    return [
      'data with content' => [['content' => []], Model::class, [], TRUE],
      'non-supported' => [[], get_class($this), [], FALSE],
    ];
  }

  /**
   * It will Denormalize.
   *
   * @test
   * @dataProvider denormalizeProvider
   */
  public function itWillDenormalizeSupportedTypes(
        array $json,
        string $expected,
        array $context = []
    ) {
    $actual = $this->denormalizer->denormalize($json, Model::class, NULL, $context);

    $this->assertEquals($expected, $actual);
  }

  /**
   * Provider.
   */
  public function gatherImagesProvider() : array {
    return [
      'minimal' => [
        [
          'content' => [],
        ],
        [],
      ],
      'sample' => [
        [
          'content' => [
            [
              'type' => 'image',
              'image' => [
                'uri' => 'https://iiif.elifesciences.org/journal-cms:editor-images/image-1.jpg',
              ],
            ],
            [
              'type' => 'paragraph',
              'text' => 'Paragraph text',
            ],
            [
              'type' => 'image',
              'image' => [
                'uri' => 'https://iiif.elifesciences.org/journal-cms:editor-images/image-2.jpg',
              ],
            ],
          ],
        ],
        [
          'public://iiif/editor-images/image-1.jpg',
          'public://iiif/editor-images/image-2.jpg',
        ],
      ],
    ];
  }

  /**
   * Gather images.
   *
   * @test
   * @dataProvider gatherImagesProvider
   */
  public function itWillGatherImages(array $json, array $expected) {
    $actual = $this->denormalizer->gatherImages($json['content']);

    $this->assertEquals($expected, $actual);
  }

  /**
   * Split strings into lines.
   */
  private function lines(array $lines, $breaks = 1) {
    return implode(str_repeat(PHP_EOL, $breaks), $lines);
  }

}
