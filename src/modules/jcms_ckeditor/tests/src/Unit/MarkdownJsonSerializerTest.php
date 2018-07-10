<?php

namespace Drupal\Tests\jcms_ckeditor\Unit;

use Drupal\jcms_ckeditor\MarkdownJsonSerializer;
use League\CommonMark\Block\Element\Document;
use League\CommonMark\DocParser;
use League\CommonMark\Environment;
use League\CommonMark\HtmlRenderer;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class MarkdownJsonSerializerTest extends \PHPUnit\Framework\TestCase
{
    /** @var MarkdownJsonSerializer */
    private $normalizer;
    /** @var DocParser */
    private $docParser;
    /** @var \Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface */
    private $mimeTypeGuesser;

    /**
     * @before
     */
    protected function setUpNormalizer()
    {
        $environment = Environment::createCommonMarkEnvironment();
        $this->mimeTypeGuesser = $this->createMock(MimeTypeGuesserInterface::class);
        $this->normalizer = new MarkdownJsonSerializer(new HtmlRenderer($environment), $this->mimeTypeGuesser);
        $this->docParser = new DocParser($environment);
    }

    /**
     * @test
     */
    public function it_is_a_normalizer()
    {
        $this->assertInstanceOf(NormalizerInterface::class, $this->normalizer);
    }

    /**
     * @test
     * @dataProvider canNormalizeProvider
     */
    public function it_can_normalize_documents($data, $format, bool $expected)
    {
        $this->assertSame($expected, $this->normalizer->supportsNormalization($data, $format));
    }

    public function canNormalizeProvider() : array
    {
        $document = new Document();

        return [
            'document' => [$document, null, true],
            'non-document' => [$this, null, false],
        ];
    }

    /**
     * @test
     * @dataProvider normalizeProvider
     */
    public function it_will_normalize_documents(array $expected, string $markdown, array $mimeTypeGuesses = [])
    {
        foreach ($mimeTypeGuesses as $uri => $mimeType) {
            $this->mimeTypeGuesser
                ->expects($this->once())
                ->method('guess')
                ->with($uri)
                ->willReturn($mimeType);
        }
        $this->assertEquals($expected, $this->normalizer->normalize($this->createDocument($markdown)));
    }

    public function normalizeProvider() : array
    {
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
            'single image' => [
                [
                    [
                        'type' => 'image',
                        'image' => [
                            'uri' => 'https://iiif.elifesciences.org/journal-cms:editor-images/image-20180501122413-1.jpeg',
                            'alt' => 'Alt text',
                            'source' => [
                                'mediaType' => 'image/jpeg',
                                'uri' => 'https://iiif.elifesciences.org/journal-cms:editor-images/image-20180501122413-1.jpeg/full/full/0/default.jpg',
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
                    ],
                ],
                "<figure alt=\"Alt text\" class=\"image\" data-fid=\"123\" data-uuid=\"UUID\" height=\"1562\" src=\"/sites/default/files/editor-images/image-20180501122413-1.jpeg\" title=\"Image title\" width=\"2500\">![Alt text](/sites/default/files/editor-images/image-20180501122413-1.jpeg \"Image title\")<figcaption>Caption</figcaption></figure>",
                [
                    'public://sites/default/files/editor-images/image-20180501122413-1.jpeg' => 'image/jpeg',
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

    private function lines(array $lines, $breaks = 1)
    {
        return implode(str_repeat(PHP_EOL, $breaks), $lines);
    }

    private function createDocument(string $markdown = '') : Document
    {
        return $this->docParser->parse($markdown);
    }
}
