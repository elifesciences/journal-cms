<?php

namespace Drupal\Tests\jcms_admin\Unit;

use Drupal\jcms_admin\MarkdownJsonSerializer;
use Drupal\jcms_admin\YouTubeInterface;
use Drupal\Tests\UnitTestCase;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\DocParser;
use League\CommonMark\Environment;
use League\CommonMark\HtmlRenderer;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Tests for MarkdownJsonSerializer.
 */
class MarkdownJsonSerializerTest extends UnitTestCase {

  use Helper;

  /**
   * Normalizer.
   *
   * @var \Drupal\jcms_admin\MarkdownJsonSerializer
   */
  private $normalizer;

  /**
   * DocParser.
   *
   * @var \League\CommonMark\DocParser
   */
  private $docParser;

  /**
   * Mime type guesser.
   *
   * @var \Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface
   */
  private $mimeTypeGuesser;

  /**
   * YouTube.
   *
   * @var \Drupal\jcms_admin\YouTubeInterface
   */
  private $youtube;

  /**
   * Setup.
   *
   * @before
   */
  protected function setUpNormalizer() {
    $environment = Environment::createCommonMarkEnvironment();
    $this->docParser = new DocParser($environment);
    $this->mimeTypeGuesser = $this->createMock(MimeTypeGuesserInterface::class);
    $this->youtube = $this->createMock(YouTubeInterface::class);
    $this->normalizer = new MarkdownJsonSerializer($this->docParser, new HtmlRenderer($environment), $this->mimeTypeGuesser, $this->youtube, new CommonMarkConverter());
  }

  /**
   * Verify that normalizer detected.
   *
   * @test
   */
  public function itIsNormalizer() {
    $this->assertInstanceOf(NormalizerInterface::class, $this->normalizer);
  }

  /**
   * It can normalize.
   *
   * @test
   * @dataProvider canNormalizeProvider
   */
  public function itCanNormalizeMarkdown($data, $format, bool $expected) {
    $this->assertSame($expected, $this->normalizer->supportsNormalization($data, $format));
  }

  /**
   * Provider.
   */
  public function canNormalizeProvider() : array {
    return [
      'markdown' => ['markdown', NULL, TRUE],
      'non-markdown' => [$this, NULL, FALSE],
    ];
  }

  /**
   * We normalizes D.
   *
   * @test
   * @dataProvider normalizeProvider
   */
  public function itWillNormalizeMarkdown(array $expected, string $markdown, array $mimeTypeGuesses = []) {
    foreach ($mimeTypeGuesses as $uri => $mimeType) {
      $this->mimeTypeGuesser
        ->expects($this->once())
        ->method('guess')
        ->with($uri)
        ->willReturn($mimeType);
    }
    $this->assertEquals($expected, $this->normalizer->normalize($markdown));
  }

  /**
   * Provider.
   */
  public function normalizeProvider() : array {
    return [
      'minimal' => [
        [],
        '',
      ],
      'single paragraph' => [
        [
          [
            'type' => 'paragraph',
            'text' => 'Single paragraph',
          ],
        ],
        'Single paragraph',
      ],
      'paragraph with &lt; and &gt;' => [
        [
          [
            'type' => 'paragraph',
            'text' => 'Text with &lt; and &gt; and &lt;figure&gt;',
          ],
        ],
        'Text with &lt; and &gt; and &lt;figure&gt;',
      ],
      'simple image' => [
        [
          [
            'type' => 'image',
            'image' => [
              'uri' => 'https://iiif.elifesciences.org/journal-cms/editor-images%2Fimage-20180501122413-1.jpeg',
              'alt' => 'Alt text',
              'source' => [
                'mediaType' => 'image/jpeg',
                'uri' => 'https://iiif.elifesciences.org/journal-cms/editor-images%2Fimage-20180501122413-1.jpeg/full/full/0/default.jpg',
                'filename' => 'image-20180501122413-1.jpeg',
              ],
              'size' => [
                'width' => 2500,
                'height' => 1562,
              ],
              'focalPoint' => [
                'x' => 50,
                'y' => 50,
              ],
            ],
            'title' => 'Caption with a <a href="https://elifesciences.org/">link</a>.',
          ],
        ],
        "<figure alt=\"Alt text\" class=\"image align-center\" data-fid=\"123\" data-uuid=\"UUID\" height=\"1562\" src=\"/sites/default/files/iiif/editor-images/image-20180501122413-1.jpeg\" title=\"Image title\" width=\"2500\">![Alt text](/sites/default/files/editor-images/image-20180501122413-1.jpeg \"Image title\")<figcaption>Caption with a [link](https://elifesciences.org/).</figcaption></figure>",
        [
          'public://iiif/editor-images/image-20180501122413-1.jpeg' => 'image/jpeg',
        ],
      ],
      'inline image' => [
        [
          [
            'type' => 'image',
            'image' => [
              'uri' => 'https://iiif.elifesciences.org/journal-cms/editor-images%2Fimage-20180501122413-1.jpeg',
              'alt' => 'Alt text',
              'source' => [
                'mediaType' => 'image/jpeg',
                'uri' => 'https://iiif.elifesciences.org/journal-cms/editor-images%2Fimage-20180501122413-1.jpeg/full/full/0/default.jpg',
                'filename' => 'image-20180501122413-1.jpeg',
              ],
              'size' => [
                'width' => 2500,
                'height' => 1562,
              ],
              'focalPoint' => [
                'x' => 50,
                'y' => 50,
              ],
            ],
            'title' => 'Caption',
            'inline' => TRUE,
          ],
        ],
        "<figure alt=\"Alt text\" class=\"image align-left\" data-fid=\"123\" data-uuid=\"UUID\" height=\"1562\" src=\"/sites/default/files/iiif/editor-images/image-20180501122413-1.jpeg\" title=\"Image title\" width=\"2500\">![Alt text](/sites/default/files/editor-images/image-20180501122413-1.jpeg \"Image title\")<figcaption>Caption</figcaption></figure>",
        [
          'public://iiif/editor-images/image-20180501122413-1.jpeg' => 'image/jpeg',
        ],
      ],
      'single table' => [
        [
          [
            'type' => 'table',
            'tables' => [
              '<table><tr><td>Cell one</td></tr></table>',
            ],
          ],
        ],
        '<table><tr><td>Cell one</td></tr></table>',
      ],
      'multiple tables' => [
        [
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
        $this->lines([
          '<table><tr><td>Cell one</td></tr></table>',
          '<table><tr><td>Cell two</td></tr></table>',
        ], 2),
      ],
      'simple list' => [
        [
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
        $this->lines([
          'Nested list:',
          '- Item 1',
          '- Item 2',
          '  - Item 2.1',
          '    1. Item 2.1.1',
        ]),
      ],
      'single blockquote' => [
        [
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
        '> Blockquote line 1',
      ],
      'simple code sample' => [
        [
          [
            'type' => 'code',
            'code' => $this->lines([
              'Code sample line 1',
              'Code sample line 2',
            ], 2),
          ],
        ],
        $this->lines([
          '```',
          'Code sample line 1',
          'Code sample line 2',
          '```',
        ], 2),
      ],
      'single section' => [
        [
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
        $this->lines([
          '# Section heading',
          'Single paragraph',
        ]),
      ],
      'preserve hierarchy' => [
        [
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
              [
                'type' => 'paragraph',
                'text' => 'Paragraph under empty section heading.',
              ],
            ],
          ],
        ],
        $this->lines([
          'Paragraph 1.',
          '# Section 1',
          'Paragraph 1 in Section 1.',
          'Paragraph 2 in Section 1.',
          'Paragraph 3 in Section 1.',
          '## Section 1.1',
          'Paragraph 1 in Section 1.1.',
          '> Blockquote 1 in Section 1.1.',
          'Paragraph 2 in Section 1.1.',
          '```',
          'Code sample 1 line 1 in Section 1.1.',
          'Code sample 1 line 2 in Section 1.1.',
          '```',
          '## Section 1.2',
          'Paragraph 1 in Section 1.2.',
          '# Section 2',
          'Paragraph 1 in Section 2.',
          '<table><tr><td>Table 1 in Section 2.</td></tr></table>',
          'Paragraph 2 in Section 2.',
          '## ',
          'Paragraph under empty section heading.',
        ], 2),
      ],
      'offset hierarchy' => [
        [
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
                    'type' => 'paragraph',
                    'text' => 'Paragraph 2 in Section 1.1.',
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
                'type' => 'paragraph',
                'text' => 'Paragraph 2 in Section 2.',
              ],
            ],
          ],
        ],
        $this->lines([
          'Paragraph 1.',
          '## Section 1',
          'Paragraph 1 in Section 1.',
          'Paragraph 2 in Section 1.',
          'Paragraph 3 in Section 1.',
          '### Section 1.1',
          'Paragraph 1 in Section 1.1.',
          'Paragraph 2 in Section 1.1.',
          '### Section 1.2',
          'Paragraph 1 in Section 1.2.',
          '## Section 2',
          'Paragraph 1 in Section 2.',
          'Paragraph 2 in Section 2.',
        ], 2),
      ],
      'first section not primary' => [
        [
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
                    'type' => 'paragraph',
                    'text' => 'Paragraph 2 in Section 1.1.',
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
                'type' => 'paragraph',
                'text' => 'Paragraph 2 in Section 2.',
              ],
            ],
          ],
        ],
        $this->lines([
          'Paragraph 1.',
          '## Section 1',
          'Paragraph 1 in Section 1.',
          'Paragraph 2 in Section 1.',
          'Paragraph 3 in Section 1.',
          '### Section 1.1',
          'Paragraph 1 in Section 1.1.',
          'Paragraph 2 in Section 1.1.',
          '### Section 1.2',
          'Paragraph 1 in Section 1.2.',
          '# Section 2',
          'Paragraph 1 in Section 2.',
          'Paragraph 2 in Section 2.',
        ], 2),
      ],
    ];
  }

}
