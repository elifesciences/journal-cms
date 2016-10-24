<?php

namespace Drupal\jcms_migrate\Plugin\migrate\process;

use DOMDocument;
use DOMNode;
use DOMNodeList;
use DOMXPath;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process the content values into a the field_content structure.
 *
 * @MigrateProcessPlugin(
 *   id = "jcms_split_content"
 * )
 */
class JCMSSplitContent extends ProcessPluginBase {

  use JMCSCheckMarkupTrait;

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!empty($value)) {
      $value = $this->tidyHtml($value);
      libxml_use_internal_errors(TRUE);
      $dom = new DomDocument();
      $dom->loadHTML('<?xml encoding="UTF-8"><html><body>' . $value . '</body></html>');

      $xpath = new DomXPath($dom);
      $body = $xpath->query('//body');
      $node_children = $body->item(0)->childNodes;
      $content = [];
      foreach ($node_children as $child) {
        if ($child->nodeType === XML_ELEMENT_NODE) {
          $content_item = NULL;
          $type = NULL;
          switch ($child->nodeName) {
            case 'p':
              $innerHTML = $this->DOMnodeInnerHTML($child);
              if (!empty($innerHTML)) {
                $content_item = [
                  'type' => 'paragraph',
                  'text' => $innerHTML,
                ];
              }
              break;
            case 'ol':
              $content_item = $this->convertListToArray($child->childNodes, TRUE);
              break;
            case 'ul':
              $content_item = $this->convertListToArray($child->childNodes, FALSE);
              break;
            case 'table':
              $content_item = [
                'type' => 'table',
                'html' => trim($dom->saveHTML($child)),
              ];
              break;
            case 'img':
              $content_item = [
                'type' => 'image',
                'image' => $child->getAttribute('src'),
              ];
              if ($alt = $child->getAttribute('alt')) {
                $content_item['alt'] = $alt;
              }
              if ($caption = $child->getAttribute('caption')) {
                $content_item['caption'] = $caption;
              }
              break;
            case 'div':
              $html = trim($dom->saveHTML($child));
              if (preg_match('/<a .*href=\"(?P<href>[^\"]+)\"[^>]*><img .*src=\"(?P<src>[^\"]+)\"></a>/', $html, $matches)) {
                $content_item = $matches;
              }
              break;
            case 'youtube':
              $content_item = [
                'type' => 'youtube',
                'id' => $child->getAttribute('id'),
              ];
              if ($width = $child->getAttribute('width')) {
                $content_item['width'] = $width;
              }
              if ($height = $child->getAttribute('height')) {
                $content_item['height'] = $height;
              }
              break;
            default:
              $content_item = [
                'type' => 'paragraph',
                'text' => $child->ownerDocument->saveHTML($child),
              ];
          }
          if (!empty($content_item)) {
            $content[] = $content_item;
          }
        }
      }
      return $content;
    }

    return NULL;
  }

  public function tidyHtml($string) {
    $string = str_replace('&nbsp;', ' ', $string);
    $string = $this->stripImgAnchor($string);
    $string = $this->captionConvert($string);
    $string = $this->youtubeConvert($string);
    $string = $this->nl2p($string);
    $string = preg_replace("/\\s+/i", ' ', $string);
    return $string;
  }

  public function nl2p($string) {
    // Remove all p and div tags and introduce line breaks.
    $string = preg_replace(['/<p[^>]*>/', '/<\/p>/', '/<div[^>]*>/', '/<\/div>/'], "\n\n", $string);
    // Introduce line breaks before and after img, table, ul and ol.
    $string = preg_replace(["/(<img [^>]*>)/", "/(<table[^>]*>)/", "/(<ul[^>]*>)/", "/(<ol[^>]*>)/", "/(<iframe[^>]*>)/"], "\n\n$1", $string);
    $string = preg_replace(["/(<img [^>]*>)/", "~(</table>)~", "~(</ul>)~", "~(</ol>)~", "~(</iframe>)~"], "$1\n\n", $string);

    $delimiter = '||||';
    $string = preg_replace("/\\s*\\n\\n+\\s*/i", $delimiter, $string);
    $split = explode($delimiter, $string);
    foreach ($split as $k => $item) {
      $item = trim($item);
      if (empty($item)) {
        unset($split[$k]);
      }
      else {
        if (!preg_match('/^(<img|<table|<ol|<ul|<youtube)/', $item)) {
          $item = '<p>' . $item . '</p>';
        }
        $split[$k] = $item;
      }
    }
    return implode("", $split);
  }

  public function stripImgAnchor($string) {
    preg_match_all("/(<a .*href=\")(?P<href>[^\"]*)([^>]*>)\\s*(?P<img_start><img .*src=\")(?P<src>[^\"]*)(?P<img_end>[^>]*>)\\s*(<\\/a>)/", $string, $matches, PREG_SET_ORDER);
    if (!empty($matches)) {
      $search = [];
      $replace = [];
      foreach ($matches as $match) {
        if ($match['href'] == $match['src']) {
          $search[] = "/" . preg_quote($match[0], "/") . "/";
          $replace[] = $match['img_start'] . $match['src'] . $match['img_end'];
        }
      }
      $string = preg_replace($search, $replace, $string);
    }
    return $string;
  }

  public function captionConvert($string) {
    preg_match_all("/\\[caption[^\\]]+\\](?P<img_start><img .*src=\"[^\"]+\"[^>\\/]*)(?P<img_end>\\s*[\\/]?>)(?P<caption>.*)\\[\\/caption\\]/", $string, $matches, PREG_SET_ORDER);
    if (!empty($matches)) {
      $search = [];
      $replace = [];
      foreach ($matches as $match) {
        $search[] = "/" . preg_quote($match[0], "/") . "/";
        $replace[] = $match['img_start'] . ' caption="' . trim($match['caption']) . '"' . $match['img_end'];
      }
      $string = preg_replace($search, $replace, $string);
    }
    return $string;
  }

  public function youtubeConvert($string) {
    $matches = [];
    preg_match_all("~<iframe [^>]*src=\"(?P<url>[^\"]+)\"[^>]*>.*</iframe>~", $string, $matches, PREG_SET_ORDER);
    if (!empty($matches)) {
      $search = [];
      $replace = [];
      foreach ($matches as $match) {
        if ($id = $this->youtubeID($match['url'])) {
          if (preg_match("~ width=\"([^\"]+)\"~", $match[0], $width)) {
            $width = sprintf(' width="%s"', $width[1]);
          }
          else {
            $width = "";
          }
          if (preg_match("~ height=\"([^\"]+)\"~", $match[0], $height)) {
            $height = sprintf(' height="%s"', $height[1]);
          }
          else {
            $height = "";
          }
          $search[] = "~" . preg_quote($match[0], "~") . "~";
          $replace[] = '<youtube id="' . $id . '"' . $width . $height . '/>';
        }
      }
      $string = preg_replace($search, $replace, $string);
    }
    return $string;
  }

  public function youtubeID($url) {
    if (preg_match('/(?:youtube(?:-nocookie)?\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|vi|e(?:mbed)?)\/|\S*?[?&]v=|\S*?[?&]vi=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $match)) {
      return $match[1];
    }
    else {
      return NULL;
    }
  }

  /**
   * @param DOMNodeList $list
   * @param bool $ordered
   * @return array
   */
  public function convertListToArray($list, $ordered = FALSE) {
    $items = [];
    foreach ($list as $item) {
      $inner_html = $this->DOMnodeInnerHTML($item);
      if (!empty($inner_html)) {
        $items[] = $inner_html;
      }
    }
    $array = [
      'type' => 'list',
      'ordered' => $ordered,
      'items' => $items,
    ];
    return $array;
  }

  /**
   * @param DOMNodeList|DOMNode $element
   * @return string
   */
  public function DOMnodeInnerHTML($element) {
    $innerHTML = '';
    if ($element instanceof DOMNode) {
      $element = $element->childNodes;
    }
    if (!empty($element)) {
      foreach ($element as $child) {
        $html = $child->ownerDocument->saveHTML($child);
        $html = str_replace('&nbsp;', ' ', $html);
        $html = $this->checkMarkup($html, 'basic_html');
        $innerHTML .= trim($html);
      }
    }
    return $innerHTML;
  }

}
