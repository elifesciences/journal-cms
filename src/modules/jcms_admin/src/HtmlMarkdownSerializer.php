<?php

namespace Drupal\jcms_admin;

use League\HTMLToMarkdown\HtmlConverter;
use PHPHtmlParser\Dom;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class HtmlMarkdownSerializer implements NormalizerInterface
{
    private $htmlConverter;
    private $bracketChar = 'ø';

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
        $html = $this->preserveOutput($object, $context);
        $html = $this->cleanHtml($html);
        $html = $this->htmlConverter->convert($html);
        $markdown = $this->prepareOutput($html);
        $markdown = preg_replace('/(<\/table>|<\/oembed>|<\/figure>|<\/elifebutton>)\s*([^\s\n])/', '$1'.PHP_EOL.PHP_EOL.'$2', $markdown);
        return trim($markdown);
    }

    private function cleanHtml(string $html) : string
    {
        $html = preg_replace_callback('~ href="([^\"]+ [^\"]*)"~', function ($matches) {
            return ' href="'.str_replace(' ', '%20', $matches[1]).'""';
        }, $html);
        $dom = new Dom();
        $dom->setOptions([
            'preserveLineBreaks' => true,
        ]);
        $dom->load($html);
        $this->preserveImgProperties($dom);

        $clean = preg_replace('/\n{2,}/', PHP_EOL.PHP_EOL, $dom->outerHtml);
        return preg_replace('/&(?!nbsp;|amp;)([^\s;]*;)/', '&amp;$1', $clean);
    }

    private function preserveOutput(string $html, array $context = []) : string
    {
        $regexes = $context['regexes'] ?? [];
        $preserve = preg_replace(array_keys($regexes), array_values($regexes), $html);
        $encode = $context['encode'] ?? [];
        $bc = $this->bracketChar;
        $preserve = preg_replace('~<(/?(code|table)[^>]*)>~', $bc.'$1'.$bc, $preserve);
        return preg_replace_callback('~'.$bc.'(code|table)[^'.$bc.']*'.$bc.'([^'.$bc.']*)'.$bc.'/\1'.$bc.'~s', function ($matches) use ($bc, $encode) {
            if ($matches[1] === 'table') {
                $matches[2] = preg_replace('/\s*'.PHP_EOL.'+\s*/', '', strip_tags($matches[2], '<thead><tbody><th></th><tr><td><img><strong><em><i><italic><strong><b><bold><sub><sup><a><linebreak>'));
            }
            $match = $matches[2];
            $before = '<'.$matches[1].'>';
            $after = '</'.$matches[1].'>';
            $prefix = preg_match('/^\n/', $match) ? PHP_EOL : '';
            $suffix = preg_match('/\n$/', $match) ? PHP_EOL : '';
            if ($matches[1] === 'code' && (!empty($prefix.$suffix))) {
                $before = $after = '```';
                $after .= PHP_EOL.PHP_EOL;
            }
            if (in_array($matches[1], $encode)) {
                $matches[2] = base64_encode(trim($matches[2]));
            }

            return $bc.'preserve'.base64_encode($before.$prefix.$matches[2].$suffix.$after).'preserve'.$bc.PHP_EOL;
        }, $preserve);
    }

    private function prepareOutput(string $html) : string
    {
        $bc = $this->bracketChar;
        $output = preg_replace_callback('~'.$bc.'preserve([^'.$bc.']*)preserve'.$bc.'~s', function ($matches) {
            return base64_decode($matches[1]);
        }, preg_replace('~(preserve'.$bc.')\s*([^\n])~', '$1'.PHP_EOL.PHP_EOL.'$2', $html));
        return preg_replace('/\n{2,}/', PHP_EOL.PHP_EOL, $output);
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
