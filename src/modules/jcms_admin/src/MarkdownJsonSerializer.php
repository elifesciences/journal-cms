<?php

namespace Drupal\jcms_admin;

use Drupal\jcms_rest\JCMSImageUriTrait;
use League\CommonMark\Block\Element\IndentedCode;
use League\CommonMark\Block\Element\FencedCode;
use League\CommonMark\Block\Element\BlockQuote;
use League\CommonMark\Block\Element\ListBlock;
use League\CommonMark\Block\Element\Paragraph;
use League\CommonMark\Block\Element\HtmlBlock;
use League\CommonMark\Block\Element\Heading;
use League\CommonMark\Block\Element\Document;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\DocParser;
use League\CommonMark\ElementRendererInterface;
use League\CommonMark\Node\Node;
use PHPHtmlParser\Dom;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Convert Markdown to Json.
 */
final class MarkdownJsonSerializer implements NormalizerInterface {

  use JCMSImageUriTrait;

  private $docParser;
  private $htmlRenderer;
  private $mimeTypeGuesser;
  private $youtube;
  private $tweet;
  private $googleMap;
  private $figshare;
  private $converter;
  private $depthOffset = NULL;
  private $iiif = '';
  private $bracketChar = 'ø';

  /**
   * Constructor.
   */
  public function __construct(
    DocParser $docParser,
    ElementRendererInterface $htmlRenderer,
    MimeTypeGuesserInterface $mimeTypeGuesser,
    YouTubeInterface $youtube,
    TweetInterface $tweet,
    GoogleMapInterface $googleMap,
    FigshareInterface $figshare,
    CommonMarkConverter $converter
  ) {
    $this->docParser = $docParser;
    $this->htmlRenderer = $htmlRenderer;
    $this->mimeTypeGuesser = $mimeTypeGuesser;
    $this->youtube = $youtube;
    $this->tweet = $tweet;
    $this->googleMap = $googleMap;
    $this->figshare = $figshare;
    $this->converter = $converter;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []) : array {
    $this->iiif = $context['iiif'] ?? 'https://iiif.elifesciences.org/journal-cms/';
    return $this->convertChildren($this->docParser->parse($object), $context);
  }

  /**
   * Convert children.
   */
  private function convertChildren(Document $document, array $context = []) : array {
    $nodes = [];
    $this->resetDepthOffset();
    foreach ($document->children() as $node) {
      if ($child = $this->convertChild($node, $context)) {
        $nodes[] = $child;
      }
    }

    return $this->implementHierarchy($nodes);
  }

  /**
   * Reset depth offset.
   */
  private function resetDepthOffset() {
    $this->depthOffset = $this->setDepthOffset(NULL, TRUE);
  }

  /**
   * Set depth offset.
   */
  private function setDepthOffset($depthOffset, bool $override = FALSE) {
    if (is_null($this->depthOffset) || $override === TRUE) {
      $this->depthOffset = $depthOffset;
    }
  }

  /**
   * Retrieve depth offset.
   */
  private function getDepthOffset() {
    return $this->depthOffset;
  }

  /**
   * Convert child.
   */
  private function convertChild(Node $node, array $context = []) : array {
    $encode = $context['encode'] ?? [];

    if ($node instanceof Heading && $rendered = $this->htmlRenderer->renderBlock($node)) {
      $depthOffset = $this->getDepthOffset();
      $heading = (int) preg_replace('/^h([1-5])$/', '$1', $rendered->getTagName());
      $title = $this->prepareOutput($rendered->getContents(), $context);

      if (empty($title)) {
        return [];
      }

      if (is_null($depthOffset) || $heading === 1) {
        $depthOffset = 1 - $heading;
        $this->setDepthOffset($depthOffset, ($heading === 1));
      }

      // Only allow 2 levels of hierarchy.
      $depth = (($heading + $depthOffset) === 1) ? 1 : 2;

      return [
        'type' => 'section',
        'title' => $title,
        'depth' => $depth,
      ];
    }
    elseif ($node instanceof HtmlBlock) {
      if ($rendered = $this->htmlRenderer->renderBlock($node)) {
        $contents = trim($rendered);
        if (preg_match('/^(<table[^>]*>)(.*)(<\/table>)/', $contents, $matches)) {
          if (in_array('table', $encode)) {
            $contents = $matches[1] . $this->prepareOutput($matches[2], $context, TRUE) . $matches[3];
          }
          return [
            'type' => 'table',
            'tables' => [$this->prepareOutput($contents, $context)],
          ];
        }
        elseif (preg_match('/^<figure.*<\/figure>/', $contents)) {
          $dom = new Dom();
          $dom->setOptions([
            'preserveLineBreaks' => TRUE,
          ]);
          $dom->load($contents);
          /** @var \PHPHtmlParser\Dom\HtmlNode $figure */
          $figure = $dom->find('figure')[0];
          $classes = preg_split('/\s+/', trim($figure->getAttribute('class') ?? ''));
          if (in_array('video', $classes) && preg_match('/<oembed>(?P<video>http[^<]+)<\/oembed>/', $contents, $matches)) {
            $uri = trim($matches['video']);
            if ($id = $this->youtube->getIdFromUri($uri)) {
              if (preg_match('~with\-caption~', $figure->getAttribute('class'))) {
                $caption = $this->prepareCaption($figure->find('figcaption'), $contents, $context);
              }
              else {
                $caption = NULL;
              }
              $dimensions = $this->youtube->getDimensions($id);
              return array_filter([
                'type' => 'youtube',
                'id' => $id,
                'width' => $dimensions['width'] ?? 16,
                'height' => $dimensions['height'] ?? 9,
                'title' => $caption,
              ]);
            }
            else {
              return [
                'type' => 'paragraph',
                'text' => sprintf('<a href="%s">%s</a>', $uri, $uri),
              ];
            }
          }
          elseif (in_array('tweet', $classes) && preg_match('/<oembed>(?P<tweet>http[^<]+)<\/oembed>/', $contents, $matches)) {
            $uri = trim($matches['tweet']);
            if ($id = $this->tweet->getIdFromUri($uri)) {
              $details = $this->tweet->getDetails($id);
              $conversation = $figure->getAttribute('data-conversation');
              $media_card = $figure->getAttribute('data-mediacard');
              return array_filter([
                'type' => 'tweet',
                'id' => $id,
                'date' => date('Y-m-d', $details['date']),
                'accountId' => $details['accountId'],
                'accountLabel' => $details['accountLabel'],
                'text' => $details['text'],
                'conversation' => !empty($conversation) && $conversation === 'true',
                'mediaCard' => !empty($media_card) && $media_card === 'true',
              ]);
            }
            else {
              return [
                'type' => 'paragraph',
                'text' => sprintf('<a href="%s">%s</a>', $uri, $uri),
              ];
            }
          }
          elseif (in_array('figshare', $classes) && !empty($figure->find('iframe'))) {
            $uri = $figure->find('iframe')->getAttribute('src');
            if (!empty($uri) && $id = $this->figshare->getIdFromUri($uri)) {
              $width = (int) $figure->getAttribute('data-width');
              $height = (int) $figure->getAttribute('data-height');
              return array_filter([
                'type' => 'figshare',
                'id' => $id,
                'title' => $this->figshare->getTitle($id),
                'width' => $width,
                'height' => $height,
              ]);
            }
            else {
              return [
                'type' => 'paragraph',
                'text' => sprintf('<a href="%s">%s</a>', $uri, $uri),
              ];
            }
          }
          elseif (in_array('gmap', $classes) && preg_match('/<oembed>(?P<gmap>http[^<]+)<\/oembed>/', $contents, $matches)) {
            $uri = trim($matches['gmap']);
            if ($id = $this->googleMap->getIdFromUri($uri)) {
              return array_filter([
                'type' => 'google-map',
                'id' => $id,
                'title' => $this->googleMap->getTitle($id),
              ]);
            }
            else {
              return [
                'type' => 'paragraph',
                'text' => sprintf('<a href="%s">%s</a>', $uri, $uri),
              ];
            }
          }
          else {
            $uri = ltrim($figure->getAttribute('src'), '/');
            if (strpos($uri, 'http') !== 0) {
              $uri = 'public://' . preg_replace('~sites/default/files/~', '', $uri);
            }
            $filemime = $this->mimeTypeGuesser->guess($uri);
            if (strpos($uri, 'public://') === 0) {
              $basename = basename($uri);
              $uri = preg_replace_callback('~^public://iiif/(.*)$~', function ($match) {
                return $this->iiif . $this->encode($match[1]);
              }, $uri);
            }
            else {
              $basename = basename($uri);
            }
            if ($filemime === 'image/png') {
              $filemime = 'image/jpeg';
              $basename = preg_replace('/\.png$/', '.jpg', $basename);
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
            $caption = $this->prepareCaption($figure->find('figcaption'), $contents, $context);
            $type = (!empty($caption) && (bool) preg_match('/profile\-left/', $figure->getAttribute('class'))) ? 'profile' : 'image';
            if ($type === 'profile') {
              $content = [
                [
                  'type' => 'paragraph',
                  'text' => $caption,
                ],
              ];
              $caption = NULL;
            }
            else {
              $content = NULL;
            }
            return array_filter([
              'type' => $type,
              'image' => [
                'uri' => $uri,
                'alt' => $figure->getAttribute('alt') ?? '',
                'source' => [
                  'mediaType' => $filemime,
                  'uri' => $uri . '/full/full/0/default.' . $ext,
                  'filename' => $basename,
                ],
                'size' => [
                  'width' => (int) $figure->getAttribute('width'),
                  'height' => (int) $figure->getAttribute('height'),
                ],
                'focalPoint' => [
                  'x' => 50,
                  'y' => 50,
                ],
              ],
              'title' => $caption,
              'inline' => (bool) preg_match('/align\-left/', $figure->getAttribute('class')),
              'content' => $content,
            ]);
          }
        }
      }
    }
    elseif ($node instanceof Paragraph) {
      if ($rendered = $this->htmlRenderer->renderBlock($node)) {
        $contents = $rendered->getContents();
        if (preg_match('/^<elifebutton.*<\/elifebutton>/', $contents)) {
          $dom = new Dom();
          $dom->load($contents);
          /** @var \PHPHtmlParser\Dom\HtmlNode $button */
          $button = $dom->find('elifebutton')[0];
          $uri = ltrim($button->getAttribute('data-href'), '/');
          $text = $button->innerHtml();
          return [
            'type' => 'button',
            'text' => $this->prepareOutput($text, $context),
            'uri' => $uri,
          ];
        }
        else {
          return [
            'type' => 'paragraph',
            'text' => $this->prepareOutput($contents, $context),
          ];
        }
      }
    }
    elseif ($node instanceof ListBlock) {
      return $this->processListBlock($node, $context);
    }
    elseif ($node instanceof BlockQuote && $rendered = $this->htmlRenderer->renderBlock($node)) {

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
    elseif (($node instanceof IndentedCode || $node instanceof FencedCode) && $contents = $node->getStringContent()) {
      if (in_array('code', $encode)) {
        $contents = $this->prepareOutput($contents, $context, TRUE);
      }
      return [
        'type' => 'code',
        'code' => html_entity_decode($this->prepareOutput($contents, $context)),
      ];
    }

    return [];
  }

  /**
   * Prepare caption.
   */
  private function prepareCaption($captions, string $contents, array $context = []) {
    if ($captions->count()) {
      $dom = new Dom();
      $dom->load($this->converter->convertToHtml(trim(preg_replace('~^.*<figcaption[^>]*>\s*(.*)\s*</figcaption>.*~', '$1', $contents))));
      if ($dom->find('p')->count()) {
        /** @var \PHPHtmlParser\Dom\HtmlNode $text */
        $text = $dom->find('p')[0];
        return $this->prepareOutput($text->innerHtml(), $context);
      }
    }

    return NULL;
  }

  /**
   * Prepare output.
   */
  private function prepareOutput($content, array $context = [], bool $decode = FALSE) {
    $regexes = $context['regexes'] ?? [];
    $output = trim(($decode) ? base64_decode($content) : $content);
    if (!empty($regexes)) {
      $output = preg_replace(array_keys($regexes), array_values($regexes), $output);
    }
    return $output;
  }

  /**
   * Implement hierarchy.
   */
  private function implementHierarchy(array $nodes) : array {
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
            }
            else {
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

  /**
   * Process list block.
   */
  private function processListBlock(ListBlock $block, $context = []) {
    $gather = function (ListBlock $list) use (&$gather, &$render, $context) {
      $items = [];
      foreach ($list->children() as $item) {
        foreach ($item->children() as $child) {
          if ($child instanceof ListBlock) {
            $items[] = [$render($child)];
          }
          elseif ($item = $this->htmlRenderer->renderBlock($child)) {
            $items[] = $this->prepareOutput($item->getContents(), $context);
          }
        }
      }

      return $items;
    };

    $render = function (ListBlock $list) use ($gather) {
      return [
        'type' => 'list',
        'prefix' => (ListBlock::TYPE_ORDERED === $list->getListData()->type) ? 'number' : 'bullet',
        'items' => $gather($list),
      ];
    };

    return $render($block);
  }

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, $format = NULL) : bool {
    return is_string($data);
  }

  /**
   * Percent encode IIIF component.
   */
  private function encode(string $string) : string {
    static $encoding = [
      '%' => '%25',
      '/' => '%2F',
      '?' => '%3F',
      '#' => '%23',
      '[' => '%5B',
      ']' => '%5D',
      '@' => '%40',
    ];

    return str_replace(array_keys($encoding), array_values($encoding), $string);
  }

}
