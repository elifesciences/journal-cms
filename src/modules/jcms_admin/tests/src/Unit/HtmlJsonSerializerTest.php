<?php

namespace Drupal\Tests\jcms_admin\Unit;

use Drupal\jcms_admin\HtmlJsonSerializer;
use Drupal\jcms_admin\HtmlMarkdownSerializer;
use Drupal\jcms_admin\MarkdownJsonSerializer;
use League\CommonMark\DocParser;
use League\CommonMark\Environment;
use League\CommonMark\HtmlRenderer;
use League\HTMLToMarkdown\HtmlConverter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class HtmlJsonSerializerTest extends TestCase
{
    /** @var HtmlJsonSerializer */
    private $normalizer;
    /** @var \Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface */
    private $mimeTypeGuesser;

    /**
     * @before
     */
    protected function setUpNormalizer()
    {
        $environment = Environment::createCommonMarkEnvironment();
        $this->mimeTypeGuesser = $this->getMock(MimeTypeGuesserInterface::class);
        $this->normalizer = new HtmlJsonSerializer(new HtmlMarkdownSerializer(new HtmlConverter()), new MarkdownJsonSerializer(new HtmlRenderer($environment), $this->mimeTypeGuesser), new DocParser($environment));
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
        $document = 'string';

        return [
            'document' => [$document, null, true],
            'non-document' => [$this, null, false],
        ];
    }

    /**
     * @test
     * @dataProvider normalizeProvider
     */
    public function it_will_normalize_html(array $expected, string $html, array $mimeTypeGuesses = [])
    {
        foreach ($mimeTypeGuesses as $uri => $mimeType) {
            $this->mimeTypeGuesser
                ->expects($this->once())
                ->method('guess')
                ->with($uri)
                ->willReturn($mimeType);
        }
        $this->assertEquals($expected, $this->normalizer->normalize($html));
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
                        'text' => '<strong>Single</strong> paragraph',
                    ],
                ],
                '<p><strong>Single</strong> paragraph</p>',
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
            'simple image' => [
                [
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
                        'title' => 'A nice picture of a field. Courtesy of <a href="https://www.pexels.com/photo/biology-blur-close-up-dragonflies-287361/">Pexels</a>.',
                    ],
                    [
                        'type' => 'paragraph',
                        'text' => 'Trailing paragraph',
                    ],
                ],
                $this->lines([
                    '<figure class="image"><img alt="" data-fid="1" data-uuid="UUID" height="2000" src="/sites/default/files/editor-images/image-20180427145110-1.jpeg" width="2000" />',
                    '<figcaption>A nice picture of a field. Courtesy of <a href="https://www.pexels.com/photo/biology-blur-close-up-dragonflies-287361/">Pexels</a>.</figcaption>',
                    '</figure>'.PHP_EOL,
                    '<p>Trailing paragraph</p>',
                ]),
                [
                    'public://sites/default/files/editor-images/image-20180427145110-1.jpeg' => 'image/jpeg',
                ],
            ],
            'image without caption' => [
                [
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
                    ],
                ],
                $this->lines([
                    '<figure class="image"><img alt="" data-fid="1" data-uuid="UUID" height="2000" src="/sites/default/files/editor-images/image-20180427145110-1.jpeg" width="2000" />',
                    '</figure>',
                ]),
                [
                    'public://sites/default/files/editor-images/image-20180427145110-1.jpeg' => 'image/jpeg',
                ],
            ],
            'image inline' => [
                [
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
                        'inline' => true,
                    ],
                ],
                $this->lines([
                    '<figure class="image align-left"><img alt="" data-fid="1" data-uuid="UUID" height="2000" src="/sites/default/files/editor-images/image-20180427145110-1.jpeg" width="2000" />',
                    '</figure>',
                ]),
                [
                    'public://sites/default/files/editor-images/image-20180427145110-1.jpeg' => 'image/jpeg',
                ],
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
                    '<ul>',
                    '<li>Item 1</li>',
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
                ],
                $this->lines([
                    '<code>',
                    'Code sample line 1'.PHP_EOL,
                    'Code sample line 2',
                    '</code>',
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
                        ],
                    ],
                ],
                $this->lines([
                    '<p>If you’re passionate about improving the quality of the early-career experience – especially in the life sciences, biomedicine and related fields – please join us.</p>'.PHP_EOL,
                    '<p>Nominations for five new members to join the eLife ECAG, for two-year terms starting on August 1, 2018, are now invited. Details on eligibility, responsibilities and the election process are available below.<br />',
                    '<b>The deadline for nominations is 23:59 (UK time) on May 28, 2018.</b></p>',
                    '<elifebutton class="elife-button--default" data-href="https://crm.elifesciences.org/crm/node/35">Nominate yourself now</elifebutton>'.PHP_EOL,
                    '<p>&nbsp;</p>'.PHP_EOL,
                    '<h1>Eligibility</h1>'.PHP_EOL,
                    '<p>&nbsp;</p>'.PHP_EOL,
                    '<p>Members of the eLife ECAG are scientists who:</p>'.PHP_EOL,
                    '<p>&nbsp;</p>'.PHP_EOL,
                    '<ul>',
                    '<li>Are studying or conducting research in the life or biological sciences or related field, as a student, medical student, postdoctoral fellow, or junior investigator.</li>',
                    '<li>Have no more than five years’ active experience in an independent position. ‘Active experience’ is intended to exclude time away for parental leave, health leave, or other reasons unrelated to research. An independent position is defined here as having secured independent funding.</li>',
                    '</ul>'.PHP_EOL,
                    '<p>&nbsp;</p>'.PHP_EOL,
                    '<h1>Nominations</h1>'.PHP_EOL,
                    '<p>&nbsp;</p>'.PHP_EOL,
                    '<p>Individuals meeting the criteria set above may nominate themselves through <a href="https://crm.elifesciences.org/crm/node/35">the nominations form</a>. During the process they will be asked to confirm their details in relation to the criteria set by eLife.</p>'.PHP_EOL,
                    '<p>&nbsp;</p>'.PHP_EOL,
                    '<p>Nominees will be asked to provide a short (~200-word) statement that describes their vision for how different approaches to research communication might improve the career development of early-stage researchers, why they are enthusiastic to join, and how they would contribute to the work of the ECAG.</p>',
                ]),
            ],
            'questions' => [
                [
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
                $this->lines([
                    '<h1>Question: Do you like my question?</h1>',
                    '<p>This is an answer to the question.</p>',
                    '<p>This is an extended answer.</p>',
                    '<blockquote>Quote</blockquote>',
                    '<p>This is not an answer.</p>',
                    '<p><strong>Question: Next question?</strong></p>',
                    '<p>OK!</p>',
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
                    '<p>Paragraph 1.</p>',
                    '<h1>Section 1</h1>',
                    '<p>Paragraph 1 in Section 1.</p>',
                    '<p>Paragraph 2 in Section 1.</p>',
                    '<p>Paragraph 3 in Section 1.</p>',
                    '<h2>Section 1.1</h2>',
                    '<p>Paragraph 1 in Section 1.1.</p>',
                    '<blockquote>Blockquote 1 in Section 1.1.</blockquote>',
                    '<p>Paragraph 2 in Section 1.1.</p>',
                    '<code>'.PHP_EOL.'Code sample 1 line 1 in Section 1.1.',
                    'Code sample 1 line 2 in Section 1.1.'.PHP_EOL.'</code>',
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
                        'width' => 16,
                        'height' => 9,
                    ],
                ],
                '<oembed>https://www.youtube.com/watch?v=oyBX9l9KzU8</oembed>',
            ],
        ];
    }

    private function lines(array $lines, $breaks = 1)
    {
        return implode(str_repeat(PHP_EOL, $breaks), $lines);
    }
}
