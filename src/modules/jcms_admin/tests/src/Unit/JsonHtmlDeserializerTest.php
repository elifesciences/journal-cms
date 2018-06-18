<?php

namespace Drupal\Tests\jcms_admin\Unit;

use Drupal\jcms_admin\JsonHtmlDeserializer;
use eLife\ApiSdk\Model\Model;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class JsonHtmlDeserializerTest extends TestCase
{
    /** @var \Drupal\jcms_admin\JsonHtmlDeserializer */
    private $denormalizer;

    /**
     * @before
     */
    protected function setUpDenormalizer()
    {
        $this->denormalizer = new JsonHtmlDeserializer();
    }

    public function denormalizeProvider() : array
    {
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
            'simple figure' => [
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
                                'attribution' => 'Image attribution',
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
                    '<figure class="image"><img alt="" data-fid="123" data-uuid="UUID" src="/sites/default/files/editor-images/image-20180427145110-1.jpeg" width="2000" height="2000" />',
                    '<figcaption>A nice picture of a field. Courtesy of <a href="https://www.pexels.com/photo/biology-blur-close-up-dragonflies-287361/">Pexels</a>.</figcaption>',
                    '</figure>',
                    '<p>Image attribution</p>',
                    '<p>Trailing paragraph</p>',
                ]),
                [
                    'fids' => [
                        'https://iiif.elifesciences.org/journal-cms:editor-images/image-20180427145110-1.jpeg' => [
                            'fid' => 123,
                            'src' => '/sites/default/files/editor-images/image-20180427145110-1.jpeg',
                        ],
                    ],
                ]
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
                    'Code sample line 1'.PHP_EOL,
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
                    '<code>'.PHP_EOL.'Code sample 1 line 1 in Section 1.1.'.PHP_EOL,
                    'Code sample 1 line 2 in Section 1.1.'.PHP_EOL.'</code>',
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
                            'preferred' => 'Adam Brooker',
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
                    '<h1>Adam Brooker CV</h1>',
                    '<ul>',
                    '<li><b>2017 - present</b>: Current position</li>',
                    '<li><b>2015 - 2017</b>: Previous position</li>',
                    '</ul>',
                ]),
            ],
        ];
    }

    /**
     * @test
     */
    public function it_is_a_denormalizer()
    {
        $this->assertInstanceOf(DenormalizerInterface::class, $this->denormalizer);
    }

    /**
     * @test
     * @dataProvider canDenormalizeProvider
     */
    public function it_can_denormalize_supported_types($data, $format, array $context, bool $expected)
    {
        $this->assertSame($expected, $this->denormalizer->supportsDenormalization($data, $format, $context));
    }

    public function canDenormalizeProvider() : array
    {
        return [
            'data with content' => [['content' => []], Model::class, [], true],
            'non-supported' => [[], get_class($this), [], false],
        ];
    }

    /**
     * @test
     * @dataProvider denormalizeProvider
     */
    public function it_will_denormalize_supported_types(
        array $json,
        string $expected,
        array $context = []
    ) {
        $actual = $this->denormalizer->denormalize($json, Model::class, null, $context);

        $this->assertEquals($expected, $actual);
    }

    private function lines(array $lines, $breaks = 1)
    {
        return implode(str_repeat(PHP_EOL, $breaks), $lines);
    }
}
