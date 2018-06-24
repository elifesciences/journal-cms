<?php

namespace Drupal\jcms_admin;

use League\HTMLToMarkdown\HtmlConverter;
use PHPHtmlParser\Dom;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class HtmlMarkdownSerializer implements NormalizerInterface
{
    private $htmlConverter;

    private $htmlConverterConfig = [
        'header_style' => 'atx',
        'italic_style' => '*',
    ];

    public function __construct(HtmlConverter $htmlConverter)
    {
        $this->htmlConverter = $htmlConverter;
        foreach ($this->htmlConverterConfig as $k => $v) {
            $this->htmlConverter->getConfig()->setOption($k, $v);
        }
    }

    /**
     * @param string $object
     */
    public function normalize($object, $format = null, array $context = []) : string
    {
        $markdown = $this->htmlConverter->convert($this->cleanHtml($object));
        return preg_replace('/(<\/table>|<\/oembed>|<\/figure>|<\/elifebutton>)([^\s\n])/', '$1'.PHP_EOL.PHP_EOL.'$2', $markdown);
    }

    private function cleanHtml(string $html) : string
    {
        $dom = new Dom();
        $dom->setOptions([
            'preserveLineBreaks' => true,
        ]);
        $dom->load($html);
        $this->cleanTables($dom);
        $this->preserveImgProperties($dom);

        return $dom->outerHtml;
    }

    private function cleanTables(Dom $dom) : Dom
    {
        $replaces = [];
        /** @var \PHPHtmlParser\Dom\HtmlNode $table */
        foreach ($dom->find('table') as $table) {
            $tableHtml = $table->outerHtml();
            $newTableHtml = preg_replace('/\r?\n/', '', strip_tags($tableHtml, '<table><thead><tbody><th></th><tr><td><img><strong><em><i><sub><sup>'));

            $replaces['pattern'][] = '~'.preg_quote($tableHtml).'~';
            $replaces['replacement'][] = $newTableHtml;
        }

        if (!empty($replaces)) {
            $dom->load(preg_replace($replaces['pattern'], $replaces['replacement'], $dom->outerHtml));
        }

        return $dom;
    }

    private function preserveImgProperties(Dom $dom) : Dom
    {
        /** @var \PHPHtmlParser\Dom\HtmlNode $figure */
        foreach ($dom->find('figure') as $figure) {
            /** @var \PHPHtmlParser\Dom\HtmlNode $img */
            if ($img = $figure->find('img')[0]) {
                foreach ($img->getAttributes() as $key => $value) {
                    $figure->setAttribute($key, $value);
                    if (!in_array($key, ['alt', 'src', 'title'])) {
                        $img->removeAttribute($key);
                    }
                }
            }
        }

        return $dom;
    }

    public function supportsNormalization($data, $format = null) : bool
    {
        return is_string($data);
    }

}
