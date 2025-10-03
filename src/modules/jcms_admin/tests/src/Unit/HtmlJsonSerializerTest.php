<?php

namespace Drupal\Tests\jcms_admin\Unit;

use Drupal\jcms_admin\FigshareInterface;
use Drupal\jcms_admin\GoogleMapInterface;
use Drupal\jcms_admin\HtmlJsonSerializer;
use Drupal\jcms_admin\HtmlMarkdownSerializer;
use Drupal\jcms_admin\MarkdownJsonSerializer;
use Drupal\jcms_admin\TweetInterface;
use Drupal\jcms_admin\YouTubeInterface;
use Drupal\Tests\UnitTestCase;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\DocParser;
use League\CommonMark\Environment;
use League\CommonMark\HtmlRenderer;
use League\HTMLToMarkdown\HtmlConverter;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Tests for HtmlJsonSerializer.
 */
class HtmlJsonSerializerTest extends UnitTestCase {

  use Helper;

  /**
   * Normalizer.
   *
   * @var \Drupal\jcms_admin\HtmlJsonSerializer
   */
  private $normalizer;

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
   * Tweet.
   *
   * @var \Drupal\jcms_admin\TweetInterface
   */
  private $tweet;

  /**
   * GoogleMap.
   *
   * @var \Drupal\jcms_admin\GoogleMapInterface
   */
  private $googleMap;

  /**
   * Figshare.
   *
   * @var \Drupal\jcms_admin\FigshareInterface
   */
  private $figshare;

  /**
   * Setup.
   *
   * @before
   */
  protected function setUpNormalizer() {
    $environment = Environment::createCommonMarkEnvironment();
    $this->mimeTypeGuesser = $this->createMock(MimeTypeGuesserInterface::class);
    $this->youtube = $this->createMock(YouTubeInterface::class);
    $this->tweet = $this->createMock(TweetInterface::class);
    $this->googleMap = $this->createMock(GoogleMapInterface::class);
    $this->figshare = $this->createMock(FigshareInterface::class);
    $this->normalizer = new HtmlJsonSerializer(new HtmlMarkdownSerializer(new HtmlConverter()), new MarkdownJsonSerializer(new DocParser($environment), new HtmlRenderer($environment), $this->mimeTypeGuesser, $this->youtube, $this->tweet, $this->googleMap, $this->figshare, new CommonMarkConverter()));
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
   * Verify that strings can be normalized.
   *
   * @test
   * @dataProvider canNormalizeProvider
   */
  public function itCanNormalizeStrings($data, $format, bool $expected) {
    $this->assertSame($expected, $this->normalizer->supportsNormalization($data, $format));
  }

  /**
   * Provider.
   */
  public function canNormalizeProvider() : array {
    return [
      'string' => ['string', NULL, TRUE],
      'non-string' => [$this, NULL, FALSE],
    ];
  }

  /**
   * We can normalize HTML.
   *
   * @test
   * @dataProvider normalizeProvider
   */
  public function itWillNormalizeHtml(
    array $expected,
    string $html,
    array $mimeTypeGuesses = [],
    array $youtubes = [],
    array $tweets = [],
    array $googleMaps = [],
    array $figshares = [],
    array $context = [
      'encode' => [
        'code',
        'table',
      ],
    ]
  ) {
    $guessCount = 0;
    foreach ($mimeTypeGuesses as $uri => $mimeType) {
      $guessCount += substr_count($html, preg_replace('/^public:\/\/iiif\//', '', $uri));
    }
    if ($guessCount > 0) {
      $this->mimeTypeGuesser
        ->expects($this->exactly($guessCount))
        ->method('guess')
        ->willReturnCallback(function (string $calledUri) use ($mimeTypeGuesses) {
          return $mimeTypeGuesses[$calledUri] ?? NULL;
        });
    }
    foreach ($youtubes as $uri => $details) {
      $details += [
        'id' => NULL,
        'width' => NULL,
        'height' => NULL,
      ];
      if ($details['id']) {
        $this->youtube
          ->expects($this->any())
          ->method('getIdFromUri')
          ->with($uri)
          ->willReturn($details['id']);
        if ($details['width'] && $details['height']) {
          $this->youtube
            ->expects($this->any())
            ->method('getDimensions')
            ->with($details['id'])
            ->willReturn([
              'width' => $details['width'],
              'height' => $details['height'],
            ]);
        }
      }
    }
    foreach ($tweets as $uri => $details) {
      $details += [
        'id' => NULL,
        'date' => NULL,
        'accountId' => NULL,
        'accountLabel' => NULL,
        'text' => NULL,
      ];
      if ($details['id']) {
        $this->tweet
          ->expects($this->any())
          ->method('getIdFromUri')
          ->with($uri)
          ->willReturn($details['id']);
        if ($details['date'] && $details['accountId'] && $details['accountLabel'] && $details['text']) {
          $this->tweet
            ->expects($this->any())
            ->method('getDetails')
            ->with($details['id'])
            ->willReturn([
              'date' => $details['date'],
              'accountId' => $details['accountId'],
              'accountLabel' => $details['accountLabel'],
              'text' => $details['text'],
            ]);
        }
      }
    }
    foreach ($googleMaps as $uri => $details) {
      $details += [
        'id' => NULL,
        'title' => NULL,
      ];
      if ($details['id']) {
        $this->googleMap
          ->expects($this->any())
          ->method('getIdFromUri')
          ->with($uri)
          ->willReturn($details['id']);
        if ($details['title']) {
          $this->googleMap
            ->expects($this->any())
            ->method('getTitle')
            ->with($details['id'])
            ->willReturn($details['title']);
        }
      }
    }
    foreach ($figshares as $uri => $details) {
      $details += [
        'id' => NULL,
        'title' => NULL,
      ];
      if ($details['id']) {
        $this->figshare
          ->expects($this->any())
          ->method('getIdFromUri')
          ->with($uri)
          ->willReturn($details['id']);
        if ($details['title']) {
          $this->figshare
            ->expects($this->any())
            ->method('getTitle')
            ->with($details['id'])
            ->willReturn($details['title']);
        }
      }
    }
    $this->assertEquals($expected, $this->normalizer->normalize($html, NULL, $context));
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
            'text' => '<strong>Single</strong> paragraph',
          ],
        ],
        '<p><strong>Single</strong> paragraph</p>',
      ],
      'link with italics' => [
        [
          [
            'type' => 'paragraph',
            'text' => '<a href="http://example.com">A link with italics: <em>Chloroidium sp.</em>UTEX.</a>',
          ],
        ],
        '<p><a href="http://example.com">A link with italics: <i>Chloroidium sp.</i>UTEX.</a></p>',
      ],
      'paragraph with &lt; and &gt; and &amp;' => [
        [
          [
            'type' => 'paragraph',
            'text' => 'Text with &lt; &amp; &gt; and &lt;figure&gt; <a href="https://elifesciences.org?foo=bar&amp;bar=foo">https://elifesciences.org?foo=bar&amp;bar=foo</a>',
          ],
        ],
        '<p>Text with &lt; &amp; &gt; and &lt;figure&gt; <a href="https://elifesciences.org?foo=bar&amp;bar=foo">https://elifesciences.org?foo=bar&amp;bar=foo</a></p>',
      ],
      'single table' => [
        [
          [
            'type' => 'table',
            'tables' => [
              '<table><tr><td>Cell øne</td></tr></table>',
            ],
          ],
        ],
        '<table><tr><td>Cell øne</td></tr></table>',
      ],
      'simple image' => [
        [
          [
            'type' => 'image',
            'image' => [
              'uri' => 'https://iiif.elifesciences.org/journal-cms/editor-images%2Fimage-20180427145110-1.png',
              'alt' => '',
              'source' => [
                'mediaType' => 'image/jpeg',
                'uri' => 'https://iiif.elifesciences.org/journal-cms/editor-images%2Fimage-20180427145110-1.png/full/full/0/default.jpg',
                'filename' => 'image-20180427145110-1.jpg',
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
            'title' => 'A nice picture of a field. Courtesy of <a href="https://www.pexels.com/photo/biology-blur-close-up-dragonflies-287361/">Pexels</a>.',
          ],
          [
            'type' => 'paragraph',
            'text' => 'Trailing paragraph',
          ],
        ],
        $this->lines([
          '<figure class="image"><img alt="" data-fid="1" data-uuid="UUID" height="2000" src="/sites/default/files/iiif/editor-images/image-20180427145110-1.png" width="2000" />',
          '<figcaption>A nice picture of a field. Courtesy of <a href="https://www.pexels.com/photo/biology-blur-close-up-dragonflies-287361/">Pexels</a>.</figcaption>',
          '</figure>' . PHP_EOL,
          '<p>Trailing paragraph</p>',
        ]),
        [
          'public://iiif/editor-images/image-20180427145110-1.png' => 'image/png',
        ],
      ],
      'image without caption' => [
        [
          [
            'type' => 'image',
            'image' => [
              'uri' => 'https://iiif.elifesciences.org/journal-cms/editor-images%2Fimage-20180427145110-1.jpeg',
              'alt' => '',
              'source' => [
                'mediaType' => 'image/jpeg',
                'uri' => 'https://iiif.elifesciences.org/journal-cms/editor-images%2Fimage-20180427145110-1.jpeg/full/full/0/default.jpg',
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
          ],
        ],
        $this->lines([
          '<figure class="image"><img alt="" data-fid="1" data-uuid="UUID" height="2000" src="/sites/default/files/iiif/editor-images/image-20180427145110-1.jpeg" width="2000" />',
          '</figure>',
        ]),
        [
          'public://iiif/editor-images/image-20180427145110-1.jpeg' => 'image/jpeg',
        ],
      ],
      'image with placeholder caption' => [
        [
          [
            'type' => 'image',
            'image' => [
              'uri' => 'https://iiif.elifesciences.org/journal-cms/editor-images%2Fimage-20180427145110-1.jpeg',
              'alt' => '',
              'source' => [
                'mediaType' => 'image/jpeg',
                'uri' => 'https://iiif.elifesciences.org/journal-cms/editor-images%2Fimage-20180427145110-1.jpeg/full/full/0/default.jpg',
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
          ],
        ],
        $this->lines([
          '<figure class="image"><img alt="" data-fid="1" data-uuid="UUID" height="2000" src="/sites/default/files/iiif/editor-images/image-20180427145110-1.jpeg" width="2000" />',
          '<figcaption>Caption</figcaption>',
          '</figure>',
        ]),
        [
          'public://iiif/editor-images/image-20180427145110-1.jpeg' => 'image/jpeg',
        ],
      ],
      'image inline' => [
        [
          [
            'type' => 'image',
            'image' => [
              'uri' => 'https://iiif.elifesciences.org/journal-cms/editor-images%2Fimage-20180427145110-1.jpeg',
              'alt' => '',
              'source' => [
                'mediaType' => 'image/jpeg',
                'uri' => 'https://iiif.elifesciences.org/journal-cms/editor-images%2Fimage-20180427145110-1.jpeg/full/full/0/default.jpg',
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
        $this->lines([
          '<figure class="image align-left"><img alt="" data-fid="1" data-uuid="UUID" height="2000" src="/sites/default/files/iiif/editor-images/image-20180427145110-1.jpeg" width="2000" />',
          '</figure>',
        ]),
        [
          'public://iiif/editor-images/image-20180427145110-1.jpeg' => 'image/jpeg',
        ],
      ],
      'image profile with caption' => [
        [
          [
            'type' => 'profile',
            'image' => [
              'uri' => 'https://iiif.elifesciences.org/journal-cms/editor-images%2Fimage-20180427145110-1.jpeg',
              'alt' => '',
              'source' => [
                'mediaType' => 'image/jpeg',
                'uri' => 'https://iiif.elifesciences.org/journal-cms/editor-images%2Fimage-20180427145110-1.jpeg/full/full/0/default.jpg',
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
            'content' => [
              [
                'type' => 'paragraph',
                'text' => 'Profile caption',
              ],
            ],
          ],
        ],
        $this->lines([
          '<figure class="image profile-left"><img alt="" data-fid="1" data-uuid="UUID" height="2000" src="/sites/default/files/iiif/editor-images/image-20180427145110-1.jpeg" width="2000" />',
          '<figcaption>Profile caption</figcaption>',
          '</figure>',
        ]),
        [
          'public://iiif/editor-images/image-20180427145110-1.jpeg' => 'image/jpeg',
        ],
      ],
      'image profile must have a caption' => [
        [
          [
            'type' => 'image',
            'image' => [
              'uri' => 'https://iiif.elifesciences.org/journal-cms/editor-images%2Fimage-20180427145110-1.jpeg',
              'alt' => '',
              'source' => [
                'mediaType' => 'image/jpeg',
                'uri' => 'https://iiif.elifesciences.org/journal-cms/editor-images%2Fimage-20180427145110-1.jpeg/full/full/0/default.jpg',
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
          ],
        ],
        $this->lines([
          '<figure class="image profile-left"><img alt="" data-fid="1" data-uuid="UUID" height="2000" src="/sites/default/files/iiif/editor-images/image-20180427145110-1.jpeg" width="2000" />',
          '</figure>',
        ]),
        [
          'public://iiif/editor-images/image-20180427145110-1.jpeg' => 'image/jpeg',
        ],
      ],
      'image with wrapping div' => [
        [
          [
            'type' => 'image',
            'image' => [
              'uri' => 'https://iiif.elifesciences.org/journal-cms/editor-images%2Fimage-20180427145110-1.jpeg',
              'alt' => '',
              'source' => [
                'mediaType' => 'image/jpeg',
                'uri' => 'https://iiif.elifesciences.org/journal-cms/editor-images%2Fimage-20180427145110-1.jpeg/full/full/0/default.jpg',
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
            'title' => 'Image caption',
          ],
        ],
        $this->lines([
          '<div class="align-center">',
          '<figure class="image profile-left"><img alt="" data-fid="1" data-uuid="UUID" height="2000" src="/sites/default/files/iiif/editor-images/image-20180427145110-1.jpeg" width="2000" /><figcaption>Image caption</figcaption><p>1</p>',
          '</figure></div>',
        ]),
        [
          'public://iiif/editor-images/image-20180427145110-1.jpeg' => 'image/jpeg',
        ],
      ],
      'multiple images with wrapping div' => [
        [
          [
            'type' => 'image',
            'image' => [
              'uri' => 'https://iiif.elifesciences.org/journal-cms/editor-images%2Fimage-20180427145110-1.jpeg',
              'alt' => '',
              'source' => [
                'mediaType' => 'image/jpeg',
                'uri' => 'https://iiif.elifesciences.org/journal-cms/editor-images%2Fimage-20180427145110-1.jpeg/full/full/0/default.jpg',
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
            'title' => 'Image caption',
            'inline' => TRUE,
          ],
          [
            'type' => 'image',
            'image' => [
              'uri' => 'https://iiif.elifesciences.org/journal-cms/editor-images%2Fimage-20180427145110-1.jpeg',
              'alt' => '',
              'source' => [
                'mediaType' => 'image/jpeg',
                'uri' => 'https://iiif.elifesciences.org/journal-cms/editor-images%2Fimage-20180427145110-1.jpeg/full/full/0/default.jpg',
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
            'title' => 'Image caption',
          ],
        ],
        $this->lines([
          '<div class="align-left">',
          '<figure class="image profile-left"><img alt="" data-fid="1" data-uuid="UUID" height="2000" src="/sites/default/files/iiif/editor-images/image-20180427145110-1.jpeg" width="2000" /><figcaption>Image caption</figcaption><p>1</p>',
          '</figure></div>',
          '<div class="align-center">',
          '<figure class="image profile-left"><img alt="" data-fid="1" data-uuid="UUID" height="2000" src="/sites/default/files/iiif/editor-images/image-20180427145110-1.jpeg" width="2000" /><figcaption>Image caption</figcaption><p>1</p>',
          '</figure></div>',
        ]),
        [
          'public://iiif/editor-images/image-20180427145110-1.jpeg' => 'image/jpeg',
        ],
      ],
      'image with caption link' => [
        [
          [
            'type' => 'image',
            'image' => [
              'uri' => 'https://iiif.elifesciences.org/journal-cms/labs-post-content%2F2017-08%2Frefigure_extension.png',
              'alt' => 'Screenshot of ReFigure extension open on PubMed central webpage',
              'source' => [
                'mediaType' => 'image/jpeg',
                'uri' => 'https://iiif.elifesciences.org/journal-cms/labs-post-content%2F2017-08%2Frefigure_extension.png/full/full/0/default.jpg',
                'filename' => 'refigure_extension.jpg',
              ],
              'size' => [
                'width' => 1926,
                'height' => 927,
              ],
              'focalPoint' => [
                'x' => 50,
                'y' => 50,
              ],
            ],
            'title' => 'The type of content in the PDF could be identified by its positioning and formatting. This image is a derivative of and attributed to Schneemann, I.; Wiese, J.; Kunz, A.L.; Imhoff, J.F. Genetic Approach for the Fast Discovery of Phenazine Producing Bacteria. <em>Mar. Drugs</em> <strong>2011</strong>, <em>9</em>, 772-789, and used under <a href="https://creativecommons.org/licenses/by/3.0/">Creative Commons Attribution License</a> (CC BY 3.0).',
          ],
        ],
        $this->lines([
          '<figure class="image align-center"><img alt="Screenshot of ReFigure extension open on PubMed central webpage" data-fid="2954" data-uuid="UUID" src="/sites/default/files/iiif/labs-post-content/2017-08/refigure_extension.png" width="1926" height="927" />',
          '<figcaption>The type of content in the PDF could be identified by its positioning and formatting. This image is a derivative of and attributed to Schneemann, I.; Wiese, J.; Kunz, A.L.; Imhoff, J.F. Genetic Approach for the Fast Discovery of Phenazine Producing Bacteria. <i>Mar. Drugs</i> <b>2011</b>, <i>9</i>, 772-789, and used under <a href="https://creativecommons.org/licenses/by/3.0/">Creative Commons Attribution License</a> (CC BY 3.0).</figcaption>',
          '</figure>',
        ]),
        [
          'public://iiif/labs-post-content/2017-08/refigure_extension.png' => 'image/png',
        ],
      ],
      'image with captioned image unchecked' => [
        [
          [
            'type' => 'paragraph',
            'text' => 'A photo with no caption - (captioned text unchecked)',
          ],
          [
            'type' => 'image',
            'image' => [
              'uri' => 'https://iiif.elifesciences.org/journal-cms/editor-images%2Fimage-20180427145110-1.jpeg',
              'alt' => '',
              'source' => [
                'mediaType' => 'image/jpeg',
                'uri' => 'https://iiif.elifesciences.org/journal-cms/editor-images%2Fimage-20180427145110-1.jpeg/full/full/0/default.jpg',
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
          ],
        ],
        $this->lines([
          '<p>A photo with no caption - (captioned text unchecked)</p>',
          '<p><img alt="" data-fid="1" data-uuid="UUID" height="2000" src="/sites/default/files/iiif/editor-images/image-20180427145110-1.jpeg" width="2000" /></p>',
        ]),
        [
          'public://iiif/editor-images/image-20180427145110-1.jpeg' => 'image/jpeg',
        ],
      ],
      'multiple tables' => [
        [
          [
            'type' => 'table',
            'tables' => [
              '<table><tr><td>Cell one with a<br />line break</td></tr></table>',
            ],
          ],
          [
            'type' => 'table',
            'tables' => [
              '<table><tr><td>Cell two with a <a href="https://elifesciences.org/">link</a></td></tr></table>',
            ],
          ],
        ],
        $this->lines([
          '<table><tr><td>Cell one with a<br />line break</td></tr></table>',
          '<table><tr><td>Cell two with a <a href="https://elifesciences.org/">link</a></td></tr></table>',
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
              'Item <strong>1</strong>',
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
          '<ul>',
          '<li>Item <b>1</b></li>',
          '<li>Item 2<ul><li>Item 2.1<ol><li>Item 2.1.1</li></ol></li></ul></li>',
          '</ul>',
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
        '<blockquote>Blockquote line 1</blockquote>',
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
          [
            'type' => 'paragraph',
            'text' => 'This is not `code`.',
          ],
        ],
        $this->lines([
          '<pre>',
          '<code>',
          'Code sample line 1' . PHP_EOL,
          'Code sample line 2</code></pre>',
          '<p>This is not `code`.</p>',
        ]),
      ],
      'another code sample' => [
        [
          [
            'type' => 'paragraph',
            'text' => 'A pattern’s JavaScript behaviour is defined in a discrete component with the same name as the pattern. This JavaScript component is referenced from the root element of the pattern’s mustache template by the attribute:',
          ],
          [
            'type' => 'code',
            'code' => 'data-behaviour="ComponentName".',
          ],
          [
            'type' => 'paragraph',
            'text' => 'For example, the content-header pattern has its associated behaviour defined in the ContentHeader class, which is found in the ContentHeader.js file. The content-header.mustache template starts with:',
          ],
          [
            'type' => 'code',
            'code' => '<div... data-behaviour="ContentHeader">...',
          ],
        ],
        $this->lines([
          '<p>A pattern’s JavaScript behaviour is defined in a discrete component with the same name as the pattern. This JavaScript component is referenced from the root element of the pattern’s mustache template by the attribute:</p>',
          '<pre><code>',
          'data-behaviour="ComponentName".',
          '</code></pre>',
          '<p>For example, the content-header pattern has its associated behaviour defined in the ContentHeader class, which is found in the ContentHeader.js file. The content-header.mustache template starts with:</p>',
          '<pre><code>',
          '<div... data-behaviour="ContentHeader">...',
          '</code></pre>',

        ]),
      ],
      'code in code' => [
        [
          [
            'type' => 'code',
            'code' => $this->lines([
              '<code executable="yes" specific-use="input" language="mini">',
              '  bars(counts_by_species)',
              '</code>',
            ]),
          ],
        ],
        $this->lines([
          '<pre><code>',
          '&lt;code executable="yes" specific-use="input" language="mini"&gt;',
          '  bars(counts_by_species)',
          '&lt;/code&gt;',
          '</code></pre>',
        ]),
      ],
      'table in code' => [
        [
          [
            'type' => 'code',
            'code' => $this->lines([
              '<table>',
              '  <tr><td>Cell one</td></tr>',
              '</table>',
            ]),
          ],
        ],
        $this->lines([
          '<pre><code>',
          '<table>',
          '  <tr><td>Cell one</td></tr>',
          '</table>',
          '</code></pre>',
        ]),
      ],
      'code in table' => [
        [
          [
            'type' => 'table',
            'tables' => [
              '<table><tr><td><code>Cell</code> one</td></tr></table>',
            ],
          ],
        ],
        $this->lines([
          '<table>',
          '  <tr><td><code>Cell</code> one</td></tr>',
          '</table>',
        ]),
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
          '<h1>Section heading</h1>',
          '<p>Single paragraph</p>',
        ]),
      ],
      'multiple sections' => [
        [
          [
            'type' => 'paragraph',
            'text' => 'If you’re passionate about improving the quality of the early-career experience – especially in the life sciences, biomedicine and related fields – please join us.',
          ],
          [
            'type' => 'paragraph',
            'text' => $this->lines([
              'Nominations for five new members to join the eLife ECAG, for two-year terms starting on August 1, 2018, are now invited. Details on eligibility, responsibilities and the election process are available below.<br /><strong>The deadline for nominations is 23:59 (UK time) on May 28, 2018.</strong>',
            ]),
          ],
          [
            'type' => 'button',
            'text' => 'Nominate yourself now',
            'uri' => 'https://crm.elifesciences.org/crm/node/35',
          ],
          [
            'type' => 'section',
            'title' => 'Eligibility',
            'content' => [
              [
                'type' => 'paragraph',
                'text' => 'Members of the eLife ECAG are scientists who:',
              ],
              [
                'type' => 'list',
                'prefix' => 'bullet',
                'items' => [
                  'Are studying or conducting research in the life or biological sciences or related field, as a student, medical student, postdoctoral fellow, or junior investigator.',
                  'Have no more than five years’ active experience in an independent position. ‘Active experience’ is intended to exclude time away for parental leave, health leave, or other reasons unrelated to research. An independent position is defined here as having secured independent funding.',
                ],
              ],
            ],
          ],
          [
            'type' => 'section',
            'title' => 'Nominations',
            'content' => [
              [
                'type' => 'paragraph',
                'text' => 'Individuals meeting the criteria set above may nominate themselves through <a href="https://crm.elifesciences.org/crm/node/35">the nominations form</a>. During the process they will be asked to confirm their details in relation to the criteria set by eLife.',
              ],
              [
                'type' => 'paragraph',
                'text' => 'Nominees will be asked to provide a short (~200-word) statement that describes their vision for how different approaches to research communication might improve the career development of early-stage researchers, why they are enthusiastic to join, and how they would contribute to the work of the ECAG.',
              ],
              [
                'type' => 'paragraph',
                'text' => 'Paragraph under empty section heading.',
              ],
            ],
          ],
        ],
        $this->lines([
          '<p>If you’re passionate about improving the quality of the early-career experience – especially in the life sciences, biomedicine and related fields – please join us.</p>' . PHP_EOL,
          '<p>Nominations for five new members to join the eLife ECAG, for two-year terms starting on August 1, 2018, are now invited. Details on eligibility, responsibilities and the election process are available below.<br />',
          '<b>The deadline for nominations is 23:59 (UK time) on May 28, 2018.</b></p>',
          '<elifebutton class="elife-button--default" data-href="https://crm.elifesciences.org/crm/node/35">Nominate yourself now</elifebutton>' . PHP_EOL,
          '<p>&nbsp;</p>' . PHP_EOL,
          '<h1>Eligibility</h1>' . PHP_EOL,
          '<p>&nbsp;</p>' . PHP_EOL,
          '<p>Members of the eLife ECAG are scientists who:</p>' . PHP_EOL,
          '<p>&nbsp;</p>' . PHP_EOL,
          '<ul>',
          '<li>Are studying or conducting research in the life or biological sciences or related field, as a student, medical student, postdoctoral fellow, or junior investigator.</li>',
          '<li>Have no more than five years’ active experience in an independent position. ‘Active experience’ is intended to exclude time away for parental leave, health leave, or other reasons unrelated to research. An independent position is defined here as having secured independent funding.</li>',
          '</ul>' . PHP_EOL,
          '<p>&nbsp;</p>' . PHP_EOL,
          '<h1>Nominations</h1>' . PHP_EOL,
          '<p>&nbsp;</p>' . PHP_EOL,
          '<p>Individuals meeting the criteria set above may nominate themselves through <a href="https://crm.elifesciences.org/crm/node/35">the nominations form</a>. During the process they will be asked to confirm their details in relation to the criteria set by eLife.</p>' . PHP_EOL,
          '<p>&nbsp;</p>' . PHP_EOL,
          '<p>Nominees will be asked to provide a short (~200-word) statement that describes their vision for how different approaches to research communication might improve the career development of early-stage researchers, why they are enthusiastic to join, and how they would contribute to the work of the ECAG.</p>',
          '<h2></h2>',
          '<p>Paragraph under empty section heading.</p>',
        ]),
      ],
      'bold edge-case' => [
        [
          [
            'type' => 'paragraph',
            'text' => '<strong>Laurent Gatto </strong>is a senior research associate in the Department of Biochemistry at the University of Cambridge<strong>, </strong>where he leads the Computational Proteomics Unit. He is currently involved in the Wellcome Trust Open Research Project, which explores the barriers to open research, and the <a href="http://bulliedintobadscience.org/">Bullied Into Bad Science</a> campaign, an initiative by and for early career researchers who aim for a fairer, more open and ethical research and publication environment. He is also a <a href="https://www.software.ac.uk/fellowship-programme">Software Sustainability Institute fellow</a>, a Data/Software Carpentry instructor and a member of <a href="http://www.openconcam.org/">OpenConCam</a>.',
          ],
        ],
        '<p><b>Laurent Gatto </b>is a senior research associate in the Department of Biochemistry at the University of Cambridge<b>, </b>where he leads the Computational Proteomics Unit. He is currently involved in the Wellcome Trust Open Research Project, which explores the barriers to open research, and the <a href="http://bulliedintobadscience.org/">Bullied Into Bad Science</a> campaign, an initiative by and for early career researchers who aim for a fairer, more open and ethical research and publication environment. He is also a <a href="https://www.software.ac.uk/fellowship-programme">Software Sustainability Institute fellow</a>, a Data/Software Carpentry instructor and a member of <a href="http://www.openconcam.org/">OpenConCam</a>.</p>',
      ],
      'preserve hierarchy' => [
        [
          [
            'type' => 'paragraph',
            'text' => 'Paragraph 1.',
          ],
          [
            'type' => 'section',
            'title' => 'Section <em>1</em>',
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
        $this->lines([
          '<p>Paragraph 1.</p>',
          '<h1>Section <i>1</i></h1>',
          '<p>Paragraph 1 in Section 1.</p>',
          '<p>Paragraph 2 in Section 1.</p>',
          '<p>Paragraph 3 in Section 1.</p>',
          '<h2>Section 1.1</h2>',
          '<p>Paragraph 1 in Section 1.1.</p>',
          '<blockquote>Blockquote 1 in Section 1.1.</blockquote>',
          '<p>Paragraph 2 in Section 1.1.</p>',
          '<pre><code>' . PHP_EOL . 'Code sample 1 line 1 in Section 1.1.',
          'Code sample 1 line 2 in Section 1.1.' . PHP_EOL . '</code></pre>',
          '<h2>Section 1.2</h2>',
          '<p>Paragraph 1 in Section 1.2.</p>',
          '<h1>Section 2</h1>',
          '<p>Paragraph 1 in Section 2.</p>',
          '<table><tr><td>Table 1 in Section 2.</td></tr></table>',
          '<p>Paragraph 2 in Section 2.</p>',
        ], 2),
      ],
      'single button' => [
        [
          [
            'type' => 'button',
            'text' => 'Button text',
            'uri' => 'http://example.com',
          ],
        ],
        '<elifebutton class="elife-button--default" data-href="http://example.com">Button text</elifebutton>',
      ],
      'single youtube' => [
        [
          [
            'type' => 'youtube',
            'id' => 'oyBX9l9KzU8',
            'width' => 2000,
            'height' => 1500,
          ],
        ],
        '<figure class="video no-caption"><oembed>https://www.youtube.com/watch?v=oyBX9l9KzU8</oembed></figure>',
        [],
        [
          'https://www.youtube.com/watch?v=oyBX9l9KzU8' => [
            'id' => 'oyBX9l9KzU8',
            'width' => 2000,
            'height' => 1500,
          ],
        ],
      ],
      'youtube with caption' => [
        [
          [
            'type' => 'youtube',
            'id' => 'uDi7EU_zKbQ',
            'width' => 1280,
            'height' => 720,
            'title' => 'eLife Community Webinar Series – Removing Barriers for Women in Science',
          ],
        ],
        $this->lines([
          '<figure class="video with-caption">',
          '<oembed>https://www.youtube.com/watch?v=uDi7EU_zKbQ</oembed>' . PHP_EOL,
          '<figcaption>eLife Community Webinar Series – Removing Barriers for Women in Science',
          '</figcaption>',
          '</figure>',
        ]),
        [],
        [
          'https://www.youtube.com/watch?v=uDi7EU\_zKbQ' => [
            'id' => 'uDi7EU_zKbQ',
            'width' => 1280,
            'height' => 720,
          ],
        ],
      ],
      'not youtube' => [
        [
          [
            'type' => 'paragraph',
            'text' => '<a href="https://vimeo.com/44314507">https://vimeo.com/44314507</a>',
          ],
        ],
        '<figure class="video no-caption"><oembed>https://vimeo.com/44314507</oembed></figure>',
        [],
        [
          'https://vimeo.com/44314507' => [
            'id' => '',
          ],
        ],
      ],
      'single tweet' => [
        [
          [
            'type' => 'tweet',
            'id' => '1244671264595288065',
            'date' => '2020-03-30',
            'text' => 'In this time of crisis and uncertainty, publishing should not be anybody’s first priority.<br><br>The last thing we want, however, is for publishing to contribute to delays, so we&#39;re changing our peer-review policies to help authors affected by the pandemic <a href="https://t.co/xfvhh1Je8X">https://t.co/xfvhh1Je8X</a> <a href="https://t.co/wVdyO9rhwB">pic.twitter.com/wVdyO9rhwB</a>',
            'accountId' => 'eLife',
            'accountLabel' => 'eLife - the journal',
            'mediaCard' => TRUE,
          ],
        ],
        '<figure class="tweet" data-conversation="false" data-mediacard="true"><oembed>https://twitter.com/eLife/status/1252168634010656768</oembed></figure>',
        [],
        [],
        [
          'https://twitter.com/eLife/status/1252168634010656768' => [
            'id' => '1244671264595288065',
            'date' => 1585491341,
            'accountId' => 'eLife',
            'accountLabel' => 'eLife - the journal',
            'text' => 'In this time of crisis and uncertainty, publishing should not be anybody’s first priority.<br><br>The last thing we want, however, is for publishing to contribute to delays, so we&#39;re changing our peer-review policies to help authors affected by the pandemic <a href="https://t.co/xfvhh1Je8X">https://t.co/xfvhh1Je8X</a> <a href="https://t.co/wVdyO9rhwB">pic.twitter.com/wVdyO9rhwB</a>',
          ],
        ],
      ],
      'single google map' => [
        [
          [
            'type' => 'google-map',
            'id' => '13cEQIsP3F9-iEVDDgradCs2Z9F-ODLyx',
            'title' => 'eLife Community Ambassadors 2019',
          ],
        ],
        '<figure class="gmap"><oembed>https://www.google.com/maps/d/u/0/viewer?mid=13cEQIsP3F9-iEVDDgradCs2Z9F-ODLyx&ll=-3.81666561775622e-14%2C-94.2847887407595&z=1</oembed></figure>',
        [],
        [],
        [],
        [
          'https://www.google.com/maps/d/u/0/viewer?mid=13cEQIsP3F9-iEVDDgradCs2Z9F-ODLyx&ll=-3.81666561775622e-14%2C-94.2847887407595&z=1' => [
            'id' => '13cEQIsP3F9-iEVDDgradCs2Z9F-ODLyx',
            'title' => 'eLife Community Ambassadors 2019',
          ],
        ],
      ],
      'single figshare' => [
        [
          [
            'type' => 'figshare',
            'id' => '8210360',
            'title' => 'Shared Open Source Infrastructure with the Libero Community',
            'width' => 568,
            'height' => 426,
          ],
        ],
        '<figure class="figshare" data-height="426" data-width="568"><iframe src="https://widgets.figshare.com/articles/8210360/embed"></iframe></figure>',
        [],
        [],
        [],
        [],
        [
          'https://widgets.figshare.com/articles/8210360/embed' => [
            'id' => '8210360',
            'title' => 'Shared Open Source Infrastructure with the Libero Community',
          ],
        ],
      ],
      'curly brackets' => [
        [
          [
            'type' => 'paragraph',
            'text' => 'Full details of the Image API can be found at <a href="http://iiif.io/api/image/2.0/">http://iiif.io/api/image/2.0/</a>. In essence, each request uses the following syntax: {scheme}://{server}{/prefix}/{identifier}/{region}/{size}/{rotation}/{quality}.{format}.For example: www.example.org/image-service/abcd1234/full/max/0/default.jpg',
          ],
        ],
        '<p>Full details of the Image API can be found at <a href="http://iiif.io/api/image/2.0/">http://iiif.io/api/image/2.0/</a>. In essence, each request uses the following syntax: {scheme}://{server}{/prefix}/{identifier}/{region}/{size}/{rotation}/{quality}.{format}.For example: www.example.org/image-service/abcd1234/full/max/0/default.jpg</p>',
      ],
      'malformed link' => [
        [
          [
            'type' => 'paragraph',
            'text' => 'Scripts: <a href="https://github.com/TechNottingham/hubot-hackbot">https://github.com/TechNottingham/hubot-hackbot</a> <br />',
          ],
          [
            'type' => 'paragraph',
            'text' => 'Back-end API: <a href="https://github.com/TechNottingham/Hack24-API/">https://github.com/TechNottingham/Hack24-API/</a> <br />',
          ],
          [
            'type' => 'paragraph',
            'text' => 'Shell: <a href="https://github.com/TechNottingham/Hackbot%20">https://github.com/TechNottingham/Hackbot </a><br />',
          ],
          [
            'type' => 'paragraph',
            'text' => 'How to use it: <a href="https://www.hack24.co.uk/how-to-use-hackbot)">https://www.hack24.co.uk/how-to-use-hackbot </a>',
          ],
          [
            'type' => 'paragraph',
            'text' => '<em>Do you have an idea or innovation to share? Send a short outline for a Labs blogpost to innovation@elifesciences.org.</em>',
          ],
          [
            'type' => 'paragraph',
            'text' => '<em>For the latest in innovation, eLife Labs and new open-source tools, sign up for <a href="https://crm.elifesciences.org/crm/node/8?_ga=2.213152084.1156933223.1498463747-1005832603.1488200227%E2%80%9D%20with%20%E2%80%9Chttps://crm.elifesciences.org/crm/tech-news?utm_source=Labs-Binder&amp;utm_medium=website&amp;utm_campaign=technews">our technology and innovation newsletter</a>. You can also follow <a href="https://twitter.com/eLifeInnovation">@eLifeInnovation</a> on Twitter. </em>',
          ],
        ],
        $this->lines([
          '<p>Scripts: <a href="https://github.com/TechNottingham/hubot-hackbot">https://github.com/TechNottingham/hubot-hackbot</a> <br /></p>',
          '<p>Back-end API: <a href="https://github.com/TechNottingham/Hack24-API/">https://github.com/TechNottingham/Hack24-API/</a> <br /></p>',
          '<p>Shell: <a href="https://github.com/TechNottingham/Hackbot ">https://github.com/TechNottingham/Hackbot </a><br /></p>',
          '<p>How to use it: <a href="https://www.hack24.co.uk/how-to-use-hackbot)">https://www.hack24.co.uk/how-to-use-hackbot </a></p>',
          '<p><i>Do you have an idea or innovation to share? Send a short outline for a Labs blogpost to innovation@elifesciences.org.</i></p>',
          '<p><i>For the latest in innovation, eLife Labs and new open-source tools, sign up for <a href="https://crm.elifesciences.org/crm/node/8?_ga=2.213152084.1156933223.1498463747-1005832603.1488200227” with “https://crm.elifesciences.org/crm/tech-news?utm_source=Labs-Binder&amp;utm_medium=website&amp;utm_campaign=technews">our technology and innovation newsletter</a>. You can also follow <a href="https://twitter.com/eLifeInnovation">@eLifeInnovation</a> on Twitter. </i></p>',
        ]),
      ],
      'placeholder text' => [
        [],
        '<p><placeholder>Type something here ...</placeholder></p>',
      ],
      'media coverage july roundup' => [
        [
          [
            'text' => 'In our latest monthly media coverage roundup, we highlight the top mentions that eLife papers generated in July. You can view the coverage, listed beneath the relevant subject areas, below.',
            'type' => 'paragraph',
          ],
          [
            'type' => 'section',
            'title' => 'In Ecology',
            'content' => [
              [
                'type' => 'image',
                'image' => [
                  'alt' => '',
                  'uri' => 'https://iiif.elifesciences.org/journal-cms/for-the-press-content%2F2025-10%2Fimage-20251001154109-2.png',
                  'size' => [
                    'width' => 1280,
                    'height' => 885,
                  ],
                  'source' => [
                    'uri' => 'https://iiif.elifesciences.org/journal-cms/for-the-press-content%2F2025-10%2Fimage-20251001154109-2.png/full/full/0/default.jpg',
                    'filename' => 'image-20251001154109-2.jpg',
                    'mediaType' => 'image/jpeg',
                  ],
                  'focalPoint' => [
                    'x' => 50,
                    'y' => 50,
                  ],
                ],
                'title' => 'A tomato plant. Image by <a href="https://pixabay.com/users/congerdesign-509903/?utm_source=link-attribution&amp;utm_medium=referral&amp;utm_campaign=image&amp;utm_content=879441">congerdesign</a> from <a href="https://pixabay.com//?utm_source=link-attribution&amp;utm_medium=referral&amp;utm_campaign=image&amp;utm_content=879441">Pixabay</a>.',
              ],
              [
                'text' => 'Seltzer et al.’s Reviewed Preprint, ‘<a href="https://doi.org/10.7554/eLife.104700.2">Female Moths Incorporate Plant Acoustic Emissions into Their Oviposition Decision-Making Process</a>’, was covered in:',
                'type' => 'paragraph',
              ],
              [
                'type' => 'list',
                'items' => [
                  '<a href="https://www.bbc.co.uk/news/articles/c8e4860n9rpo">BBC News</a> – Animals react to secret sounds from plants, say scientists',
                  '<a href="https://www.reuters.com/science/israeli-research-finds-that-when-plants-talk-insects-listen-2025-07-15/">Reuters</a> and <a href="https://www.yahoo.com/news/israeli-research-finds-plants-talk-143538886.html">Yahoo! News</a> – Israeli research finds that when plants talk, insects listen',
                  '<a href="https://edition.cnn.com/2025/07/24/science/moths-listen-understand-plant-noises">CNN</a> – Breakthrough discovery shows that moths listen to plants — and avoid the noisy ones',
                  '<a href="https://www.jpost.com/science/article-861248">The Jerusalem Post</a> – Israeli scientists: Stressed plants emit ultrasonic sounds influencing insect behavior',
                  '<a href="https://www.israelhayom.com/2025/07/15/the-bug-whisperer-israeli-scientists-decode-natures-hidden-language/">Israel Hayom</a> – The bug whisperer: Israeli scientists decode nature\'s hidden language',
                  '<a href="https://www.ynetnews.com/environment/article/hkimvim8xg">Ynetnews</a> – Moths can hear plants: Israeli study finds insects respond to plant distress calls',
                  '<a href="https://www.jewishnews.co.uk/insects-hear-plants-talking-in-distress-israeli-study-reveals-groundbreaking-study/">Jewish News</a> – Insects hear plants ‘talking’ in distress, Israeli groundbreaking study reveals',
                  '<a href="https://www.jns.org/tau-researchers-discover-first-evidence-of-auditory-interaction-between-plants-and-animals/">Jewish News Syndicate (JNS)</a> – TAU researchers discover first evidence of auditory interaction between plants and animals',
                  '<a href="https://english.news.cn/20250716/4bbd1e5afd054b0ab56e4150108e98f8/c.html">Xinhua (China)</a> – Researchers find moths &quot;listen to&quot; ultrasonic sound from tomato plants',
                  '<a href="https://www.ndtv.com/science/insects-listen-when-plants-talk-finds-israeli-study-8885237%20https://www.labrujulaverde.com/en/2025/07/animals-documented-for-the-first-time-responding-to-sounds-emitted-by-plants/">NDTV (India)</a> – Insects Listen When Plants Talk, Finds Israeli Study',
                  '<a href="https://timesofindia.indiatimes.com/life-style/home-garden/insects-can-hear-when-plants-talk-finds-groundbreaking-study/articleshow/122830049.cms">The Times of India</a> – Insects can hear when plants talk, finds groundbreaking study',
                  '<a href="https://economictimes.indiatimes.com/news/international/us/moths-can-hear-and-decode-plant-sound-signals-for-reproductive-decisions-new-study-reveals/articleshow/122901324.cms?from=mdr">The Economic Times (India)</a> – Moths can hear and decode plant sound signals for reproductive decisions: New study reveals',
                  '<a href="https://www.greenmatters.com/pn/scientists-find-the-first-evidence-of-insects-choosing-egg-laying-site-based-on-plants-sounds">Green Matters</a> – Scientists Find the First Evidence of Insects Choosing Egg-Laying Site Based on Plants’ Sounds',
                  '<a href="https://www.ecowatch.com/animals-plants-acoustic-interaction.html">EcoWatch</a> – Scientists Find First Evidence of Auditory Interaction Between Animals and Plants: Study',
                  '<a href="https://www.iflscience.com/for-the-first-time-an-animal-has-been-shown-responding-to-plant-produced-sounds-80009">IFLScience</a> – For The First Time, An Animal Has Been Shown Responding To Plant-Produced Sounds',
                  '<a href="https://www.sciencealert.com/moths-dont-like-to-lay-their-eggs-on-plants-that-are-screaming">Science Alert</a> – Moths Don\'t Like to Lay Their Eggs on Plants That Are Screaming',
                ],
                'prefix' => 'bullet',
              ],
            ],
          ],
          [
            'type' => 'section',
            'title' => '<strong>In Evolutionary Biology</strong>',
            'content' => [
              [
                'type' => 'image',
                'image' => [
                  'alt' => '',
                  'uri' => 'https://iiif.elifesciences.org/journal-cms/for-the-press-content%2F2025-10%2Fimage-20251001162702-3.png',
                  'size' => [
                    'width' => 4194,
                    'height' => 3015,
                  ],
                  'source' => [
                    'uri' => 'https://iiif.elifesciences.org/journal-cms/for-the-press-content%2F2025-10%2Fimage-20251001162702-3.png/full/full/0/default.jpg',
                    'filename' => 'image-20251001162702-3.jpg',
                    'mediaType' => 'image/jpeg',
                  ],
                  'focalPoint' => [
                    'x' => 50,
                    'y' => 50,
                  ],
                ],
                'title' => 'Dinaledi skeletal remains. Image credit: Berger et al. (CC BY 4.0)',
              ],
              [
                'text' => 'Berger et al.’s Research Article, ‘<a href="https://doi.org/10.7554/eLife.89106.3">Evidence for deliberate burial of the dead by <em>Homo naledi</em></a>’, was picked up in:',
                'type' => 'paragraph',
              ],
              [
                'type' => 'list',
                'items' => [
                  '<a href="https://www.iflscience.com/homo-naledi-may-have-buried-its-dead-after-all-peer-reviewer-accepts-80661">IFLScience</a> and <a href="https://www.msn.com/en-us/news/technology/homo-naledi-may-have-buried-its-dead-after-all-peer-reviewer-accepts/ar-AA1LNObX">MSN</a> – Homo Naledi May Have Buried Its Dead After All, Peer Reviewer Accepts',
                  '<a href="https://www.newscientist.com/article/mg26735532-600-homo-naledis-burial-practices-could-change-what-it-means-to-be-human/">New Scientist</a> – Homo naledi\'s burial practices could change what it means to be human',
                  '<a href="https://www.newscientist.com/article/2487980-what-were-ancient-humans-thinking-when-they-began-to-bury-their-dead/">New Scientist</a> – What were ancient humans thinking when they began to bury their dead?',
                  '<a href="https://scienceandculture.com/2025/07/investigation-of-ancient-burials-yields-surprises/">Science &amp; Culture Today</a> – Investigation of Ancient Burials Yields Surprises',
                  '<a href="https://www.discoverwildlife.com/prehistoric-life/human-species-lived-alongside-us">Discover Wildlife</a> – It turns out we aren\'t as unique as we think we are: Here are 5 ancient human species that once lived alongside us',
                  '<a href="https://economictimes.indiatimes.com/news/international/us/five-ancient-human-species-that-lived-alongside-modern-humans-revealed-new-insights-into-our-prehistoric-cousins/articleshow/122960996.cms?from=mdr">The Economic Times</a> (India) – Five ancient human species that lived alongside modern humans revealed: New insights into our prehistoric cousins',
                ],
                'prefix' => 'bullet',
              ],
            ],
          ],
          [
            'type' => 'section',
            'title' => '<strong>In Ecology and Evolutionary Biology</strong>',
            'content' => [
              [
                'type' => 'image',
                'image' => [
                  'alt' => '',
                  'uri' => 'https://iiif.elifesciences.org/journal-cms/for-the-press-content%2F2025-10%2Fimage-20251001160311-6.png',
                  'size' => [
                    'width' => 1280,
                    'height' => 855,
                  ],
                  'source' => [
                    'uri' => 'https://iiif.elifesciences.org/journal-cms/for-the-press-content%2F2025-10%2Fimage-20251001160311-6.png/full/full/0/default.jpg',
                    'filename' => 'image-20251001160311-6.jpg',
                    'mediaType' => 'image/jpeg',
                  ],
                  'focalPoint' => [
                    'x' => 50,
                    'y' => 50,
                  ],
                ],
                'title' => 'A young adult male chimpanzee (Jeje) cracking nuts using stone tools. Image credit: Dora Biro (<a href="https://creativecommons.org/licenses/by/4.0/deed.en">CC BY 4.0</a>)',
              ],
              [
                'text' => 'Howard-Spink et al.’s Research Article, ‘<a href="https://doi.org/10.7554/eLife.105411.3">Old age variably impacts chimpanzee engagement and efficiency in stone tool use</a>’, was covered in:',
                'type' => 'paragraph',
              ],
              [
                'type' => 'list',
                'items' => [
                  '<a href="https://mailchi.mp/nature/daily-briefing-13893801?e=fcfcb4cba6">Nature briefing (July 15)</a>',
                  '<a href="https://www.earth.com/news/what-aging-chimpanzees-can-teach-us-about-ourselves/">Earth.com</a> – What aging chimpanzees can teach us about ourselves',
                ],
                'prefix' => 'bullet',
              ],
              [
                'text' => 'The Research Article by Smit and Robbins, ‘<a href="https://doi.org/10.7554/eLife.107093.3">Risk-taking incentives predict aggression heuristics in female gorillas</a>’, was featured in:',
                'type' => 'paragraph',
              ],
              [
                'type' => 'list',
                'items' => [
                  '<a href="https://scienceblog.com/wildscience/2025/07/25/hungry-pregnant-and-bold-why-female-gorillas-take-big-risks/">Science Blog</a> – Hungry, Pregnant, and Bold: Why Female Gorillas Take Big Risks',
                ],
                'prefix' => 'bullet',
              ],
            ],
          ],
          [
            'type' => 'section',
            'title' => '<strong>In Neuroscience</strong>',
            'content' => [
              [
                'type' => 'image',
                'image' => [
                  'alt' => '',
                  'uri' => 'https://iiif.elifesciences.org/journal-cms/for-the-press-content%2F2025-10%2Fimage-20251001162550-2.png',
                  'size' => [
                    'width' => 3474,
                    'height' => 2082,
                  ],
                  'source' => [
                    'uri' => 'https://iiif.elifesciences.org/journal-cms/for-the-press-content%2F2025-10%2Fimage-20251001162550-2.png/full/full/0/default.jpg',
                    'filename' => 'image-20251001162550-2.jpg',
                    'mediaType' => 'image/jpeg',
                  ],
                  'focalPoint' => [
                    'x' => 50,
                    'y' => 50,
                  ],
                ],
                'title' => '[Add caption when available]',
              ],
              [
                'text' => 'Park, Sipe et al.’s Research Article, ‘<a href="https://doi.org/10.7554/eLife.107298.3">Astrocytic modulation of population encoding in mouse visual cortex via GABA transporter 3 revealed by multiplexed CRISPR/Cas9 gene editing</a>’, was covered in:',
                'type' => 'paragraph',
              ],
              [
                'type' => 'list',
                'items' => [
                  '<a href="https://scienceblog.com/brains-silent-partners-how-astrocytes-keep-visual-neurons-in-sync/">Science Blog</a> – Brain’s Silent Partners: How Astrocytes Keep Visual Neurons in Sync',
                  '<a href="https://neurosciencenews.com/astrocytes-gaba-neuron-29529/">Neuroscience News</a> – How Astrocytes Keep Neural Teams in Sync',
                ],
                'prefix' => 'bullet',
              ],
              [
                'text' => 'Schmidig et al.’s Research Article, ‘<a href="https://doi.org/10.7554/eLife.89601.2">Episodic long-term memory formation during slow-wave sleep</a>’ was highlighted by:',
                'type' => 'paragraph',
              ],
              [
                'type' => 'list',
                'items' => [
                  '<a href="https://www.sleepfoundation.org/how-sleep-works/can-you-learn-a-language-while-sleeping">Sleep Foundation</a> – Can You Learn a Language While Sleeping?',
                ],
                'prefix' => 'bullet',
              ],
              [
                'text' => 'Salehinejad et al.’s Research Article, ‘<a href="https://doi.org/10.7554/eLife.69308">Sleep-dependent upscaled excitability, saturated neuroplasticity, and modulated cognition in the human brain</a>’, was picked up by:',
                'type' => 'paragraph',
              ],
              [
                'type' => 'list',
                'items' => [
                  '<a href="https://www.mindbodygreen.com/articles/study-finds-sleep-deprivation-directly-impacts-cognition-and-memory-to">mindbodygreen</a> – Sleep-Deprived? Here\'s How It Actually Impacts Your Brain (&amp; What To Do About It)',
                ],
                'prefix' => 'bullet',
              ],
              [
                'text' => 'Nartker et al.’s Research Article, ‘<a href="https://doi.org/10.7554/eLife.100337.3">Sensitivity to visual features in inattentional blindness</a>’, was featured in:',
                'type' => 'paragraph',
              ],
              [
                'type' => 'list',
                'items' => [
                  '<a href="https://www.thetransmitter.org/attention/attention-not-necessary-for-visual-awareness-large-study-suggests/">The Transmitter</a> – Attention not necessary for visual awareness, large study suggests',
                ],
                'prefix' => 'bullet',
              ],
              [
                'text' => 'Phillips et al.’s Research Article, ‘<a href="https://doi.org/10.7554/eLife.88775.2">Endogenous oscillatory rhythms and interactive contingencies jointly influence infant attention during early infant-caregiver interaction</a>’, was covered in:',
                'type' => 'paragraph',
              ],
              [
                'type' => 'list',
                'items' => [
                  '<a href="https://www.zmescience.com/science/psychology-science/whos-really-in-charge-by-12-months-old-your-baby-is-already-guiding-you/">ZME Science</a> – Who’s Really in Charge? By 12 Months Old, Your Baby Is Already Guiding You',
                ],
                'prefix' => 'bullet',
              ],
              [
                'text' => 'The Tools and Resources article by de Vries, Siegle and Koch, ‘<a href="https://doi.org/10.7554/eLife.85550">Sharing neurophysiology data from the Allen Brain Observatory</a>’, was featured in:',
                'type' => 'paragraph',
              ],
              [
                'type' => 'list',
                'items' => [
                  '<a href="https://www.thetransmitter.org/open-neuroscience-and-data-sharing/neurosciences-open-data-revolution-is-just-getting-started/">The Transmitter</a> – Neuroscience’s open-data revolution is just getting started',
                ],
                'prefix' => 'bullet',
              ],
              [
                'text' => 'Power et al.’s Research Article, ‘<a href="https://doi.org/10.7554/eLife.98662.4">Photoreceptor loss does not recruit neutrophils despite strong microglial activation</a>’, was covered in:',
                'type' => 'paragraph',
              ],
              [
                'type' => 'list',
                'items' => [
                  '<a href="https://neurosciencenews.com/microglia-retina-vision-29520/">Neuroscience News</a> – Immune Cells Ignore Retinal Damage While Microglia Step',
                ],
                'prefix' => 'bullet',
              ],
            ],
          ],
        ],
        $this->lines([
          '<p>In our latest monthly media coverage roundup, we highlight the top mentions that eLife papers generated in July. You can view the coverage, listed beneath the relevant subject areas, below.</p>',
          '',
          '<h2>In Ecology</h2>',
          '',
          '<div class="align-center">',
          '<figure class="image"><img alt="" data-uuid="1c91031e-7853-4695-8168-d52832b7945a" height="885" src="/sites/default/files/iiif/for-the-press-content/2025-10/image-20251001154109-2.png" width="1280" /><figcaption>A tomato plant. Image by <a href="https://pixabay.com/users/congerdesign-509903/?utm_source=link-attribution&amp;utm_medium=referral&amp;utm_campaign=image&amp;utm_content=879441">congerdesign</a> from <a href="https://pixabay.com//?utm_source=link-attribution&amp;utm_medium=referral&amp;utm_campaign=image&amp;utm_content=879441">Pixabay</a>.</figcaption></figure></div>',
          '',
          '<p>Seltzer et al.’s Reviewed Preprint, ‘<a href="https://doi.org/10.7554/eLife.104700.2">Female Moths Incorporate Plant Acoustic Emissions into Their Oviposition Decision-Making Process</a>’, was covered in:</p>',
          '',
          '<ul><li><a href="https://www.bbc.co.uk/news/articles/c8e4860n9rpo">BBC News</a> – Animals react to secret sounds from plants, say scientists</li>',
          '	<li><a href="https://www.reuters.com/science/israeli-research-finds-that-when-plants-talk-insects-listen-2025-07-15/">Reuters</a> and <a href="https://www.yahoo.com/news/israeli-research-finds-plants-talk-143538886.html">Yahoo! News</a> – Israeli research finds that when plants talk, insects listen</li>',
          '	<li><a href="https://edition.cnn.com/2025/07/24/science/moths-listen-understand-plant-noises">CNN</a> – Breakthrough discovery shows that moths listen to plants — and avoid the noisy ones</li>',
          '	<li><a href="https://www.jpost.com/science/article-861248">The Jerusalem Post</a> – Israeli scientists: Stressed plants emit ultrasonic sounds influencing insect behavior</li>',
          '	<li><a href="https://www.israelhayom.com/2025/07/15/the-bug-whisperer-israeli-scientists-decode-natures-hidden-language/">Israel Hayom</a> – The bug whisperer: Israeli scientists decode nature\'s hidden language</li>',
          '	<li><a href="https://www.ynetnews.com/environment/article/hkimvim8xg">Ynetnews</a> – Moths can hear plants: Israeli study finds insects respond to plant distress calls</li>',
          '	<li><a href="https://www.jewishnews.co.uk/insects-hear-plants-talking-in-distress-israeli-study-reveals-groundbreaking-study/">Jewish News</a> – Insects hear plants ‘talking’ in distress, Israeli groundbreaking study reveals</li>',
          '	<li><a href="https://www.jns.org/tau-researchers-discover-first-evidence-of-auditory-interaction-between-plants-and-animals/">Jewish News Syndicate (JNS)</a> – TAU researchers discover first evidence of auditory interaction between plants and animals</li>',
          '	<li><a href="https://english.news.cn/20250716/4bbd1e5afd054b0ab56e4150108e98f8/c.html">Xinhua (China)</a> – Researchers find moths "listen to" ultrasonic sound from tomato plants</li>',
          '	<li><a href="https://www.ndtv.com/science/insects-listen-when-plants-talk-finds-israeli-study-8885237%20https://www.labrujulaverde.com/en/2025/07/animals-documented-for-the-first-time-responding-to-sounds-emitted-by-plants/">NDTV (India)</a> – Insects Listen When Plants Talk, Finds Israeli Study</li>',
          '	<li><a href="https://timesofindia.indiatimes.com/life-style/home-garden/insects-can-hear-when-plants-talk-finds-groundbreaking-study/articleshow/122830049.cms">The Times of India</a> – Insects can hear when plants talk, finds groundbreaking study</li>',
          '	<li><a href="https://economictimes.indiatimes.com/news/international/us/moths-can-hear-and-decode-plant-sound-signals-for-reproductive-decisions-new-study-reveals/articleshow/122901324.cms?from=mdr">The Economic Times (India)</a> – Moths can hear and decode plant sound signals for reproductive decisions: New study reveals</li>',
          '	<li><a href="https://www.greenmatters.com/pn/scientists-find-the-first-evidence-of-insects-choosing-egg-laying-site-based-on-plants-sounds">Green Matters</a> – Scientists Find the First Evidence of Insects Choosing Egg-Laying Site Based on Plants’ Sounds</li>',
          '	<li><a href="https://www.ecowatch.com/animals-plants-acoustic-interaction.html">EcoWatch</a> – Scientists Find First Evidence of Auditory Interaction Between Animals and Plants: Study</li>',
          '	<li><a href="https://www.iflscience.com/for-the-first-time-an-animal-has-been-shown-responding-to-plant-produced-sounds-80009">IFLScience</a> – For The First Time, An Animal Has Been Shown Responding To Plant-Produced Sounds</li>',
          '	<li><a href="https://www.sciencealert.com/moths-dont-like-to-lay-their-eggs-on-plants-that-are-screaming">Science Alert</a> – Moths Don\'t Like to Lay Their Eggs on Plants That Are Screaming</li>',
          '</ul><h2><strong>In Evolutionary Biology</strong></h2>',
          '',
          '<figure class="image"><img alt="" data-uuid="0a9d8ad6-80c6-45b8-9e68-6fe9a14858aa" height="3015" src="/sites/default/files/iiif/for-the-press-content/2025-10/image-20251001162702-3.png" width="4194" /><figcaption>Dinaledi skeletal remains. Image credit: Berger et al. (CC BY 4.0)</figcaption></figure><p>Berger et al.’s Research Article, ‘<a href="https://doi.org/10.7554/eLife.89106.3">Evidence for deliberate burial of the dead by <em>Homo naledi</em></a>’, was picked up in:</p>',
          '',
          '<ul><li><a href="https://www.iflscience.com/homo-naledi-may-have-buried-its-dead-after-all-peer-reviewer-accepts-80661">IFLScience</a> and <a href="https://www.msn.com/en-us/news/technology/homo-naledi-may-have-buried-its-dead-after-all-peer-reviewer-accepts/ar-AA1LNObX">MSN</a> – Homo Naledi May Have Buried Its Dead After All, Peer Reviewer Accepts</li>',
          '	<li><a href="https://www.newscientist.com/article/mg26735532-600-homo-naledis-burial-practices-could-change-what-it-means-to-be-human/">New Scientist</a> – Homo naledi\'s burial practices could change what it means to be human</li>',
          '	<li><a href="https://www.newscientist.com/article/2487980-what-were-ancient-humans-thinking-when-they-began-to-bury-their-dead/">New Scientist</a> – What were ancient humans thinking when they began to bury their dead?</li>',
          '	<li><a href="https://scienceandculture.com/2025/07/investigation-of-ancient-burials-yields-surprises/">Science &amp; Culture Today</a> – Investigation of Ancient Burials Yields Surprises</li>',
          '	<li><a href="https://www.discoverwildlife.com/prehistoric-life/human-species-lived-alongside-us">Discover Wildlife</a> – It turns out we aren\'t as unique as we think we are: Here are 5 ancient human species that once lived alongside us</li>',
          '	<li><a href="https://economictimes.indiatimes.com/news/international/us/five-ancient-human-species-that-lived-alongside-modern-humans-revealed-new-insights-into-our-prehistoric-cousins/articleshow/122960996.cms?from=mdr">The Economic Times</a> (India) – Five ancient human species that lived alongside modern humans revealed: New insights into our prehistoric cousins</li>',
          '</ul><h2><strong>In Ecology and Evolutionary Biology</strong></h2>',
          '',
          '<div class="align-center">',
          '<figure class="image"><img alt="" data-uuid="2351d828-a7c9-4ca2-82c5-f0ebbb7c1041" height="855" src="/sites/default/files/iiif/for-the-press-content/2025-10/image-20251001160311-6.png" width="1280" /><figcaption>A young adult male chimpanzee (Jeje) cracking nuts using stone tools. Image credit: Dora Biro (<a href="https://creativecommons.org/licenses/by/4.0/deed.en">CC BY 4.0</a>)</figcaption></figure></div>',
          '',
          '<p>Howard-Spink et al.’s Research Article, ‘<a href="https://doi.org/10.7554/eLife.105411.3">Old age variably impacts chimpanzee engagement and efficiency in stone tool use</a>’, was covered in:</p>',
          '',
          '<ul><li><a href="https://mailchi.mp/nature/daily-briefing-13893801?e=fcfcb4cba6">Nature briefing (July 15)</a></li>',
          '	<li><a href="https://www.earth.com/news/what-aging-chimpanzees-can-teach-us-about-ourselves/">Earth.com</a> – What aging chimpanzees can teach us about ourselves</li>',
          '</ul><p>The Research Article by Smit and Robbins, ‘<a href="https://doi.org/10.7554/eLife.107093.3">Risk-taking incentives predict aggression heuristics in female gorillas</a>’, was featured in:</p>',
          '',
          '<ul><li><a href="https://scienceblog.com/wildscience/2025/07/25/hungry-pregnant-and-bold-why-female-gorillas-take-big-risks/">Science Blog</a> – Hungry, Pregnant, and Bold: Why Female Gorillas Take Big Risks</li>',
          '</ul><h2><strong>In Neuroscience</strong></h2>',
          '',
          '<div class="align-center">',
          '<figure class="image"><img alt="" data-uuid="b3e7b482-4d1b-4af1-b9ac-bc2a899b9977" height="2082" src="/sites/default/files/iiif/for-the-press-content/2025-10/image-20251001162550-2.png" width="3474" /><figcaption>[Add caption when available]</figcaption></figure></div>',
          '',
          '<p>Park, Sipe et al.’s Research Article, ‘<a href="https://doi.org/10.7554/eLife.107298.3">Astrocytic modulation of population encoding in mouse visual cortex via GABA transporter 3 revealed by multiplexed CRISPR/Cas9 gene editing</a>’, was covered in:</p>',
          '',
          '<ul><li><a href="https://scienceblog.com/brains-silent-partners-how-astrocytes-keep-visual-neurons-in-sync/">Science Blog</a> – Brain’s Silent Partners: How Astrocytes Keep Visual Neurons in Sync</li>',
          '	<li><a href="https://neurosciencenews.com/astrocytes-gaba-neuron-29529/">Neuroscience News</a> – How Astrocytes Keep Neural Teams in Sync</li>',
          '</ul><p>Schmidig et al.’s Research Article, ‘<a href="https://doi.org/10.7554/eLife.89601.2">Episodic long-term memory formation during slow-wave sleep</a>’ was highlighted by:</p>',
          '',
          '<ul><li><a href="https://www.sleepfoundation.org/how-sleep-works/can-you-learn-a-language-while-sleeping">Sleep Foundation</a> – Can You Learn a Language While Sleeping?</li>',
          '</ul><p>Salehinejad et al.’s Research Article, ‘<a href="https://doi.org/10.7554/eLife.69308">Sleep-dependent upscaled excitability, saturated neuroplasticity, and modulated cognition in the human brain</a>’, was picked up by:</p>',
          '',
          '<ul><li><a href="https://www.mindbodygreen.com/articles/study-finds-sleep-deprivation-directly-impacts-cognition-and-memory-to">mindbodygreen</a> – Sleep-Deprived? Here\'s How It Actually Impacts Your Brain (&amp; What To Do About It)</li>',
          '</ul><p>Nartker et al.’s Research Article, ‘<a href="https://doi.org/10.7554/eLife.100337.3">Sensitivity to visual features in inattentional blindness</a>’, was featured in:</p>',
          '',
          '<ul><li><a href="https://www.thetransmitter.org/attention/attention-not-necessary-for-visual-awareness-large-study-suggests/">The Transmitter</a> – Attention not necessary for visual awareness, large study suggests</li>',
          '</ul><p>Phillips et al.’s Research Article, ‘<a href="https://doi.org/10.7554/eLife.88775.2">Endogenous oscillatory rhythms and interactive contingencies jointly influence infant attention during early infant-caregiver interaction</a>’, was covered in:</p>',
          '',
          '<ul><li><a href="https://www.zmescience.com/science/psychology-science/whos-really-in-charge-by-12-months-old-your-baby-is-already-guiding-you/">ZME Science</a> – Who’s Really in Charge? By 12 Months Old, Your Baby Is Already Guiding You</li>',
          '</ul><p>The Tools and Resources article by de Vries, Siegle and Koch, ‘<a href="https://doi.org/10.7554/eLife.85550">Sharing neurophysiology data from the Allen Brain Observatory</a>’, was featured in:</p>',
          '',
          '<ul><li><a href="https://www.thetransmitter.org/open-neuroscience-and-data-sharing/neurosciences-open-data-revolution-is-just-getting-started/">The Transmitter</a> – Neuroscience’s open-data revolution is just getting started</li>',
          '</ul><p>Power et al.’s Research Article, ‘<a href="https://doi.org/10.7554/eLife.98662.4">Photoreceptor loss does not recruit neutrophils despite strong microglial activation</a>’, was covered in:</p>',
          '',
          '<ul><li><a href="https://neurosciencenews.com/microglia-retina-vision-29520/">Neuroscience News</a> – Immune Cells Ignore Retinal Damage While Microglia Step</li>',
          '</ul>',
        ]),
        [
          'public://iiif/for-the-press-content/2025-10/image-20251001154109-2.png' => 'image/png',
          'public://iiif/for-the-press-content/2025-10/image-20251001162702-3.png' => 'image/png',
          'public://iiif/for-the-press-content/2025-10/image-20251001160311-6.png' => 'image/png',
          'public://iiif/for-the-press-content/2025-10/image-20251001162550-2.png' => 'image/png',
        ],
      ],
    ];
  }

}
