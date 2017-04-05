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
      $dom = new DomDocument();
      libxml_use_internal_errors(TRUE);
      $dom->loadHTML('<?xml encoding="UTF-8"><html><body>' . $value . '</body></html>');
      libxml_clear_errors();

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
            case 'code':
              $innerHTML = $this->DOMnodeInnerHTML($child);
              if (!empty($innerHTML)) {
                $content_item = [
                  'type' => 'code',
                  'code' => base64_decode($innerHTML),
                ];
              }
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
          if (!empty($content_item) && (empty($this->configuration['limit_types']) || in_array($content_item['type'], (array) $this->configuration['limit_types']))) {
            $content[] = $content_item;
          }
        }
      }
      return $content;
    }

    return NULL;
  }

  /**
   * Perform variations operations to tidy html.
   *
   * @param $string
   * @return mixed|string
   */
  public function tidyHtml($string) {
    $string = htmlspecialchars_decode(htmlentities($string, ENT_NOQUOTES, 'UTF-8', FALSE), ENT_NOQUOTES);
    $string = preg_replace(['/&(nbsp|#xA0);/', '~<(/?)strong>~'], [' ', '<$1b>'], $string);
    $string = $this->imgStyleDimensions($string);
    $string = $this->codeConvert($string);
    $string = $this->stripImgAnchor($string);
    $string = $this->captionConvert($string);
    $string = $this->youtubeConvert($string);
    $string = $this->nl2p($string);
    $string = preg_replace("/\\s+/i", ' ', $string);
    return $string;
  }

  /**
   * Convert img width and height style values to img properties.
   *
   * @param $string
   * @return mixed
   */
  public function imgStyleDimensions($string) {
    if (preg_match_all('/(?P<img_with_style>(<img [^>]*)(style=\")(?P<style>[^\"]+))(\"[^>]*)/', $string, $matches, PREG_SET_ORDER)) {
      $patterns = [];
      $replaces = [];
      foreach ($matches as $match) {
        $styles = [];
        preg_match_all('/^\s*(?P<name>[^:]+)(:\s*(?P<value>.+))?;\s*$/m', preg_replace(['/;\s*/', '/([^\s;])\s*$/'], [";\n", '$1;'], $match['style']), $style_matches, PREG_SET_ORDER);
        foreach ($style_matches as $style_match) {
          $styles[$style_match['name']] = isset($style_match['value']) ? $style_match['value'] : NULL;
        }

        $new_img = $match['img_with_style'];
        foreach (['width', 'height'] as $dimension) {
          if (isset($styles[$dimension]) && !preg_match("/ " . $dimension . "=\"/", $match['img_with_style'])) {
            $new_img = preg_replace('/( style=\")/', sprintf(' %s="%d"$1', $dimension, (int) preg_replace('/[^0-9]/', '', $styles[$dimension])), $new_img);
          }
        }

        if ($match['img_with_style'] != $new_img) {
          $patterns[] = '/' . preg_quote($match['img_with_style'], '/') . '/';
          $replaces[] = $new_img;
        }
      }
      $string = preg_replace($patterns, $replaces, $string);
    }

    return $string;
  }

  /**
   * Convert newline to paragraphs
   *
   * @param $string
   * @return string
   */
  public function nl2p($string) {
    // Remove all p and div tags and introduce line breaks.
    $string = preg_replace(['/<p[^>]*>/', '/<\/p>/', '/<div[^>]*>/', '/<\/div>/'], "\n\n", $string);
    // Introduce line breaks before and after img, table, ul and ol.
    $string = preg_replace(["/(<img [^>]*>)/", "/(<table[^>]*>)/", "/(<ul[^>]*>)/", "/(<ol[^>]*>)/", "/(<iframe[^>]*>)/", "/(<code[^>]*>)/"], "\n\n$1", $string);
    $string = preg_replace(["/(<img [^>]*>)/", "~(</table>)~", "~(</ul>)~", "~(</ol>)~", "~(</iframe>)~", "~(</code>)~"], "$1\n\n", $string);

    $split = preg_split("~\\s*\\n+\\s*~", $string);
    foreach ($split as $k => $item) {
      $item = trim($item);
      if (empty($item)) {
        unset($split[$k]);
      }
      else {
        if (!preg_match('/^(<img|<table|<ol|<ul|<youtube|<code)/', $item)) {
          $item = '<p>' . $item . '</p>';
        }
        $split[$k] = $item;
      }
    }
    return implode("", $split);
  }

  /**
   * Strip anchor tags that wrap images.
   *
   * @param $string
   * @return mixed
   */
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

  /**
   * Prepare markup to introduce captions.
   *
   * @param $string
   * @return mixed
   */
  public function captionConvert($string) {
    preg_match_all("/\\[caption[^\\]]*\\](?P<img_start><img .*src=\"[^\"]+\"[^>\\/]*)(?P<img_end>\\s*[\\/]?>)(?P<caption>.*)\\[\\/caption\\]/", $string, $matches, PREG_SET_ORDER);
    if (!empty($matches)) {
      $search = [];
      $replace = [];
      foreach ($matches as $match) {
        $search[] = "/" . preg_quote($match[0], "/") . "/";
        $replace[] = preg_replace(['/\s\s+/', '~(/)?>~'], [' ', '$1>'], $match['img_start'] . ' caption="' . trim($match['caption']) . '"' . $match['img_end']);
      }
      $string = preg_replace($search, $replace, $string);
    }
    return $string;
  }

  /**
   * Convert code to base64 string to preserve it.
   *
   * @param $string
   * @return mixed
   */
  public function codeConvert($string) {
    preg_match_all("~<code[^>]*>(?P<code>.*?)</code>~s", $string, $matches, PREG_SET_ORDER);
    if (!empty($matches)) {
      $search = [];
      $replace = [];
      foreach ($matches as $match) {
        $search[] = "~" . preg_quote($match[0]) . "~";
        $replace[] = "<code>" . base64_encode(trim($match['code'])) . "</code>";
      }
      $string = preg_replace($search, $replace, $string);
    }
    return $string;
  }

  /**
   * Detect youtube videos.
   *
   * @param $string
   * @return mixed
   */
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

  /**
   * Detect youtube ID.
   *
   * @param string $url
   * @return string|NULL
   */
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
        $innerHTML .= $html;
      }
    }
    return preg_replace(["~([^\\s])<a ~", "~([^\\s])<(sub|sup|b|i)>~", "~</(a|sub|sup|b|i)>([A-z0-9\\(])~"], ['$1 <a ', '$1 <$2>', '</$1> $2'], $innerHTML);
  }

}
