<?php

namespace Drupal\Tests\jcms_admin\Unit;

use Drupal\jcms_admin\HtmlMarkdownSerializer;
use League\HTMLToMarkdown\HtmlConverter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class HtmlMarkdownSerializerTest extends TestCase
{
    /** @var HtmlMarkdownSerializer */
    private $normalizer;

    /**
     * @before
     */
    protected function setUpNormalizer()
    {
        $this->normalizer = new HtmlMarkdownSerializer(new HtmlConverter());
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
    public function it_will_normalize_html(string $expected, string $html)
    {
        $this->assertEquals($expected, $this->normalizer->normalize($html));
    }

    public function normalizeProvider() : array
    {
        return [
            'minimal' => [
                '',
                '',
            ],
            'single paragraph' => [
                'Single paragraph',
                '<p>Single paragraph</p>',
            ],
            'single image' => [
                "<figure alt=\"Alt text\" class=\"image\" data-fid=\"123\" data-uuid=\"UUID\" height=\"1562\" src=\"/sites/default/files/editor-images/image-20180501122413-1.jpeg\" title=\"Image title\" width=\"2500\">![Alt text](/sites/default/files/editor-images/image-20180501122413-1.jpeg \"Image title\")<figcaption>Caption</figcaption></figure>",
                "<figure class=\"image\">\n<img alt=\"Alt text\" title=\"Image title\" data-fid=\"123\" data-uuid=\"UUID\" height=\"1562\" src=\"/sites/default/files/editor-images/image-20180501122413-1.jpeg\" width=\"2500\" />\n<figcaption>Caption</figcaption>\n</figure>"
            ],
            'single table' => [
                '<table><tr><td>Cell one</td></tr></table>',
                '<table><tr><td>Cell one</td></tr></table>',
            ],
            'multiple tables' => [
                $this->lines([
                    '<table><tr><td>Cell one</td></tr></table>',
                    '<table><tr><td>Cell two</td></tr></table>',
                ], 2),
                $this->lines([
                    '<table><tr><td>Cell one</td></tr></table>',
                    '<table><tr><td>Cell two</td></tr></table>',
                ], 2),
            ],
            'table multiple lines' => [
                '<table> <tr> <td> Cell one </td></tr></table>',
                $this->lines([
                    '<table>',
                    '  <tr>',
                    '    <td>',
                    '      <p>Cell one</p>',
                    '    </td>',
                    '  </tr>',
                    '</table>',
                ]),
            ],
            'simple list' => [
                $this->lines([
                    'Nested list:'.PHP_EOL,
                    '- Item 1',
                    '- Item 2',
                    '  - Item 2.1',
                    '      1. Item 2.1.1',
                ]),
                $this->lines([
                    'Nested list:',
                    '<ul>',
                    '<li>Item 1</li>',
                    '<li>Item 2<ul><li>Item 2.1<ol><li>Item 2.1.1</li></ol></li></ul></li>',
                    '</ul>',
                ]),
            ],
            'single blockquote' => [
                '> Blockquote line 1',
                '<blockquote>Blockquote line 1</blockquote>',
            ],
            'simple code sample' => [
                $this->lines([
                    '```',
                    'Code sample line 1',
                    'Code sample line 2',
                    '```',
                ], 2),
                $this->lines([
                    '<code>',
                    'Code sample line 1'.PHP_EOL,
                    'Code sample line 2',
                    '</code>',
                ]),
            ],
            'single section' => [
                $this->lines([
                    '# Section heading',
                    'Single paragraph',
                ], 2),
                $this->lines([
                    '<h1>Section heading</h1>',
                    '<p>Single paragraph</p>',
                ]),
            ],
            'questions' => [
                $this->lines([
                    '# Question: Do you like my question?',
                    'This is an answer to the question.',
                    'This is an extended answer.',
                    '> Quote',
                    'This is not an answer.',
                    '**Question: Next question?**',
                    'OK!',
                ], 2),
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
        ];
    }

    private function lines(array $lines, $breaks = 1)
    {
        return implode(str_repeat(PHP_EOL, $breaks), $lines);
    }
}
