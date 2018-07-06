<?php

namespace Drupal\Tests\jcms_admin\Unit;

use Drupal\jcms_admin\HtmlJsonSerializer;
use Drupal\jcms_admin\HtmlMarkdownSerializer;
use Drupal\jcms_admin\MarkdownJsonSerializer;
use League\CommonMark\CommonMarkConverter;
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
        $this->normalizer = new HtmlJsonSerializer(new HtmlMarkdownSerializer(new HtmlConverter()), new MarkdownJsonSerializer(new DocParser($environment), new HtmlRenderer($environment), $this->mimeTypeGuesser, new CommonMarkConverter()));
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
    public function it_will_normalize_html(array $expected, string $html, array $mimeTypeGuesses = [], array $context = ['encode' => ['code', 'table']])
    {
        foreach ($mimeTypeGuesses as $uri => $mimeType) {
            $this->mimeTypeGuesser
                ->expects($this->once())
                ->method('guess')
                ->with($uri)
                ->willReturn($mimeType);
        }
        $this->assertEquals($expected, $this->normalizer->normalize($html, null, $context));
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
                            'uri' => 'https://iiif.elifesciences.org/journal-cms:editor-images/image-20180427145110-1.png',
                            'alt' => '',
                            'source' => [
                                'mediaType' => 'image/jpeg',
                                'uri' => 'https://iiif.elifesciences.org/journal-cms:editor-images/image-20180427145110-1.png/full/full/0/default.jpg',
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
                    '</figure>'.PHP_EOL,
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
                    '<figure class="image"><img alt="" data-fid="1" data-uuid="UUID" height="2000" src="/sites/default/files/iiif/editor-images/image-20180427145110-1.jpeg" width="2000" />',
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
                    '<figure class="image align-left"><img alt="" data-fid="1" data-uuid="UUID" height="2000" src="/sites/default/files/iiif/editor-images/image-20180427145110-1.jpeg" width="2000" />',
                    '</figure>',
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
                            'uri' => 'https://iiif.elifesciences.org/journal-cms:labs-post-content/2017-08/refigure_extension.png',
                            'alt' => 'Screenshot of ReFigure extension open on PubMed central webpage',
                            'source' => [
                                'mediaType' => 'image/jpeg',
                                'uri' => 'https://iiif.elifesciences.org/journal-cms:labs-post-content/2017-08/refigure_extension.png/full/full/0/default.jpg',
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
                        'title' => '<strong>Capturing</strong> an <em>image</em> from an open-access publication on PubMed Central using the ReFigure Chrome extension. Screenshot from <a href="https://www.ncbi.nlm.nih.gov/pmc/">https://www.ncbi.nlm.nih.gov/pmc/</a> with the active ReFigure extension.',
                    ],
                ],
                $this->lines([
                    '<figure class="image align-center"><img alt="Screenshot of ReFigure extension open on PubMed central webpage" data-fid="2954" data-uuid="UUID" src="/sites/default/files/iiif/labs-post-content/2017-08/refigure_extension.png" width="1926" height="927" />',
                    '<figcaption><b>Capturing</b> an <i>image</i> from an open-access publication on PubMed Central using the ReFigure Chrome extension. Screenshot from <a href="https://www.ncbi.nlm.nih.gov/pmc/">https://www.ncbi.nlm.nih.gov/pmc/</a> with the active ReFigure extension.</figcaption>',
                    '</figure>',
                ]),
                [
                    'public://iiif/labs-post-content/2017-08/refigure_extension.png' => 'image/png',
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
                ],
                $this->lines([
                    '<code>',
                    'Code sample line 1'.PHP_EOL,
                    'Code sample line 2',
                    '</code>',
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
                    '<code>',
                    'data-behaviour="ComponentName".',
                    '</code>',
                    '<p>For example, the content-header pattern has its associated behaviour defined in the ContentHeader class, which is found in the ContentHeader.js file. The content-header.mustache template starts with:</p>',
                    '<code>',
                    '<div... data-behaviour="ContentHeader">...',
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
            'bold edge-case' => [
                [
                    [
                        'type' => 'paragraph',
                        'text' => '<strong>Laurent Gatto </strong>is a senior research associate in the Department of Biochemistry at the University of Cambridge<strong>, </strong>where he leads the Computational Proteomics Unit. He is currently involved in the Wellcome Trust Open Research Project, which explores the barriers to open research, and the <a href="http://bulliedintobadscience.org/">Bullied Into Bad Science</a> campaign, an initiative by and for early career researchers who aim for a fairer, more open and ethical research and publication environment. He is also a <a href="https://www.software.ac.uk/fellowship-programme">Software Sustainability Institute fellow</a>, a Data/Software Carpentry instructor and a member of <a href="http://www.openconcam.org/">OpenConCam</a>.',
                    ],
                ],
                '<p><b>Laurent Gatto </b>is a senior research associate in the Department of Biochemistry at the University of Cambridge<b>, </b>where he leads the Computational Proteomics Unit. He is currently involved in the Wellcome Trust Open Research Project, which explores the barriers to open research, and the <a href="http://bulliedintobadscience.org/">Bullied Into Bad Science</a> campaign, an initiative by and for early career researchers who aim for a fairer, more open and ethical research and publication environment. He is also a <a href="https://www.software.ac.uk/fellowship-programme">Software Sustainability Institute fellow</a>, a Data/Software Carpentry instructor and a member of <a href="http://www.openconcam.org/">OpenConCam</a>.</p>',
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
                        'text' => 'How to use it: <a href="https://www.hack24.co.uk/how-to-use-hackbot">https://www.hack24.co.uk/how-to-use-hackbot </a>',
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
                    '<p>How to use it: <a href="https://www.hack24.co.uk/how-to-use-hackbot">https://www.hack24.co.uk/how-to-use-hackbot </a></p>',
                    '<p><i>Do you have an idea or innovation to share? Send a short outline for a Labs blogpost to innovation@elifesciences.org.</i></p>',
                    '<p><i>For the latest in innovation, eLife Labs and new open-source tools, sign up for <a href="https://crm.elifesciences.org/crm/node/8?_ga=2.213152084.1156933223.1498463747-1005832603.1488200227” with “https://crm.elifesciences.org/crm/tech-news?utm_source=Labs-Binder&amp;utm_medium=website&amp;utm_campaign=technews">our technology and innovation newsletter</a>. You can also follow <a href="https://twitter.com/eLifeInnovation">@eLifeInnovation</a> on Twitter. </i></p>',
                ]),
            ],
//            'styled figcaption' => [
//                [],
//                $this->lines([
//                    '<figure class="image align-center"><img alt="A PDF side by side by an image of the PDF elements as colour blocks" data-fid="1444" data-uuid="UUID" src="/sites/default/files/iiif/labs-post-content/2017-08/fig8-merge.png" width="1238" height="802" />',
//                    '<figcaption>This image is a derivative of and attributed to Schneemann, I.; Wiese, J.; Kunz, A.L.; Imhoff, J.F. Genetic Approach for the Fast Discovery of Phenazine Producing Bacteria. <i>Mar. Drugs</i> <b>2011</b>, <i>9</i>, 772-789, and used under <a href="https://creativecommons.org/licenses/by/3.0/">Creative Commons Attribution License</a> (CC BY 3.0).</figcaption>',
//                    '</figure>',
//                ]),
//            ],
        ];
    }

    private function lines(array $lines, $breaks = 1)
    {
        return implode(str_repeat(PHP_EOL, $breaks), $lines);
    }
}
