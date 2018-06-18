<?php

namespace Drupal\jcms_admin;

use League\CommonMark\Block\Element;
use League\CommonMark\ElementRendererInterface;
use League\CommonMark\Node\Node;
use PHPHtmlParser\Dom;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class MarkdownJsonSerializer implements NormalizerInterface
{

    private $htmlRenderer;
    private $mimeTypeGuesser;
    private $depthOffset = null;

    public function __construct(ElementRendererInterface $htmlRenderer, MimeTypeGuesserInterface $mimeTypeGuesser)
    {
        $this->htmlRenderer = $htmlRenderer;
        $this->mimeTypeGuesser = $mimeTypeGuesser;
    }

    /**
     * @param Element\Document $object
     */
    public function normalize($object, $format = null, array $context = []) : array
    {
        return $this->convertChildren($object);
    }

    private function convertChildren(Element\Document $document) : array
    {
        $nodes = [];
        $this->resetDepthOffset();
        foreach ($document->children() as $node) {
            if ($child = $this->convertChild($node)) {
                $nodes[] = $child;
            }
        }

        return $this->implementHierarchy($this->implementQuestions($nodes));
    }

    private function resetDepthOffset()
    {
        $this->depthOffset = $this->setDepthOffset(null, true);
    }

    private function setDepthOffset($depthOffset, bool $override = false)
    {
        if (is_null($this->depthOffset) || $override === true) {
            $this->depthOffset = $depthOffset;
        }
    }

    private function getDepthOffset()
    {
        return $this->depthOffset;
    }

    /**
     * @return array|null
     */
    private function convertChild(Node $node)
    {
        switch (true) {
            case $node instanceof Element\Heading:
                if ($rendered = $this->htmlRenderer->renderBlock($node)) {
                    $depthOffset = $this->getDepthOffset();
                    $heading = (int) preg_replace('/^h([1-5])$/', '$1', $rendered->getTagName());
                    if (is_null($depthOffset) || $heading === 1) {
                        $depthOffset = 1 - $heading;
                        $this->setDepthOffset($depthOffset, ($heading === 1));
                    }

                    // Only allow 2 levels of hierarchy.
                    $depth = (($heading + $depthOffset) === 1) ? 1 : 2;

                    return [
                        'type' => 'section',
                        'title' => $rendered->getContents(),
                        'depth' => $depth,
                    ];
                }
                break;
            case $node instanceof Element\HtmlBlock:
                if ($rendered = $this->htmlRenderer->renderBlock($node)) {
                    $contents = trim($rendered);
                    if (preg_match('/^<table.*<\/table>/', $contents)) {
                        return [
                            'type' => 'table',
                            'tables' => [$contents],
                        ];
                    } elseif (preg_match('/^<figure.*<\/figure>/', $contents)) {
                        $dom = new Dom();
                        $dom->setOptions([
                            'preserveLineBreaks' => true,
                        ]);
                        $dom->load($contents);
                        /** @var \PHPHtmlParser\Dom\HtmlNode $figure */
                        $figure = $dom->find('figure')[0];
                        $uri = ltrim($figure->getAttribute('src'), '/');
                        if (strpos($uri, 'http') !== 0) {
                            $uri = 'public://'.$uri;
                        }
                        $filemime = $this->mimeTypeGuesser->guess($uri);
                        if ($filemime == 'image/png') {
                            $filemime = 'image/jpeg';
                            $uri = preg_replace('/\.png$/', '.jpg', $uri);
                        }

                        if (strpos($uri, 'public://') === 0) {
                            $uri = preg_replace(['~^public://~', '~:sites/default/files/~'], ['https://iiif.elifesciences.org/journal-cms:', ':'], $uri);
                        }
                        switch ($filemime) {
                            case 'image/gif':
                                $ext = 'gif';
                                break;

                            case 'image/png':
                                $ext = 'png';
                                break;

                            default:
                                $ext = 'jpg';
                        }
                        $caption = null;
                        if ($figure->find('figcaption')) {
                            /** @var \PHPHtmlParser\Dom\HtmlNode $caption */
                            $captionNode = $dom->find('figcaption')[0];
                            $caption = trim($captionNode->innerHtml());
                        }
                        return array_filter([
                            'type' => 'image',
                            'image' => array_filter([
                                'uri' => $uri,
                                'alt' => $figure->getAttribute('alt') ?? null,
                                'source' => [
                                    'mediaType' => $filemime,
                                    'uri' => $uri.'/full/full/0/default.'.$ext,
                                    'filename' => basename($uri),
                                ],
                                'size' => [
                                    'width' => $figure->getAttribute('width'),
                                    'height' => $figure->getAttribute('height'),
                                ],
                                'focalPoint' => [
                                    'x' => 50,
                                    'y' => 50,
                                ],
                            ]),
                            'title' => $caption,
                        ]);
                    }
                }
                break;
            case $node instanceof Element\Paragraph:
                if ($rendered = $this->htmlRenderer->renderBlock($node)) {
                    return [
                        'type' => 'paragraph',
                        'text' => $rendered->getContents(),
                    ];
                }
                break;
            case $node instanceof Element\ListBlock:
                return $this->processListBlock($node);
                break;
            case $node instanceof Element\BlockQuote:
                if ($rendered = $this->htmlRenderer->renderBlock($node)) {
                    return [
                        'type' => 'quote',
                        'text' => [
                            [
                                'type' => 'paragraph',
                                'text' => trim(preg_replace('/^[\s]*<p>(.*)<\/p>[\s]*$/s', '$1', $rendered->getContents())),
                            ],
                        ],
                    ];
                }
                break;
            case $node instanceof Element\FencedCode:
            case $node instanceof Element\IndentedCode:
                if ($rendered = $this->htmlRenderer->renderBlock($node)) {
                    return [
                        'type' => 'code',
                        'code' => trim(preg_replace('/^[\s]*<code>(.*)<\/code>[\s]*$/s', '$1', $rendered->getContents())),
                    ];
                }
                break;
        }

        return null;
    }

    private function implementQuestions(array $nodes) : array
    {
        $question_found = false;
        $new_nodes = [];
        for ($i = 0; $i < count($nodes); $i++) {
            $node = $nodes[$i];
            if ($node['type'] === 'section' && preg_match('~^Question: (?P<question>.+)$~i', $node['title'], $match)) {
                $question_found = true;
                $new_nodes[] = [
                    'type' => 'question',
                    'question' => $match['question'],
                    'answer' => [],
                ];
                continue;
            }
            elseif ($node['type'] === 'paragraph') {
                if (preg_match('~^<strong>Question: (?P<question>.+)</strong>$~i', $node['text'], $match)) {
                    $question_found = true;
                    $new_nodes[] = [
                        'type' => 'question',
                        'question' => $match['question'],
                        'answer' => [],
                    ];
                    continue;
                }
                elseif ($question_found) {
                    $new_nodes[count($new_nodes)-1]['answer'][] = $node;
                    continue;
                }
            }
            $new_nodes[] = $node;
            $question_found = false;
        }
        return $new_nodes;
    }

    private function implementHierarchy(array $nodes) : array
    {
        // Organise 2 levels of section.
        for ($level = 2; $level > 0; $level--) {
            $hierarchy = [];
            for ($i = 0; $i < count($nodes); $i++) {
                $node = $nodes[$i];

                if ($node['type'] === 'section' && isset($node['depth']) && $node['depth'] === $level) {
                    unset($node['depth']);
                    for ($j = $i + 1; $j < count($nodes); $j++) {
                        $sectionNode = $nodes[$j];
                        if ($sectionNode['type'] === 'section' && isset($sectionNode['depth']) && $sectionNode['depth'] <= $level) {
                            break;
                        } else {
                            $node['content'][] = $sectionNode;
                        }
                    }
                    $i = $j - 1;
                    if (empty($node['content'])) {
                        continue;
                    }
                }
                $hierarchy[] = $node;
            }
            $nodes = $hierarchy;
        };

        return $hierarchy ?? [];
    }

    private function processListBlock(Element\ListBlock $block)
    {
        $gather = function (Element\ListBlock $list) use (&$gather, &$render) {
            $items = [];
            foreach ($list->children() as $item) {
                foreach ($item->children() as $child) {
                    if ($child instanceof Element\ListBlock) {
                        $items[] = [$render($child)];
                    } elseif ($item = $this->htmlRenderer->renderBlock($child)) {
                        $items[] = $item->getContents();
                    }
                }
            }

            return $items;
        };

        $render = function (Element\ListBlock $list) use ($gather) {
            return [
                'type' => 'list',
                'prefix' => (Element\ListBlock::TYPE_ORDERED === $list->getListData()->type) ? 'number' : 'bullet',
                'items' => $gather($list),
            ];
        };

        return $render($block);
    }

    public function supportsNormalization($data, $format = null) : bool
    {
        return $data instanceof Element\Document;
    }

}
