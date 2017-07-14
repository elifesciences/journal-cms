<?php

namespace Drupal\jcms_rest;

use Drupal\Component\Utility\Html;

trait JCMSHtmlHelperTrait {

  /**
   * Split paragraphs into array of paragraphs and lists.
   *
   * @param string $paragraphs
   * @return array
   */
  public function splitParagraphs(string $paragraphs) {
    $dom = Html::load($paragraphs);
    $xpath = new \DOMXPath($dom);
    foreach ($xpath->query('//body/ul | //body/ol') as $node) {
      $html = $node->ownerDocument->saveHTML($node);
      $new_node = $dom->createElement($node->nodeName);
      $frag = $dom->createDocumentFragment();
      $frag->appendXML(preg_replace(['~<br\s*/?>~', '~(?!</(ul|ol)>\s*)(\n|\t)+~'], '', $html));
      $new_node->appendChild($frag);
      $node->parentNode->replaceChild($new_node->firstChild, $node);
    }
    $html = preg_replace('~</(ul|ol)><(ol|ul)~', "</$1>\n<$2", Html::serialize($dom));
    $split = preg_split('/\n+/', $html);

    return array_map([$this, 'convertHtmlListToSchema'], array_filter($split));
  }

  /**
   * Convert HTML list on single line to schema structure.
   *
   * @param string $html
   * @return array|string
   */
  public function convertHtmlListToSchema(string $html) {
    if (!preg_match('~^\s*<(ul|ol)[^>]*>.*</\1>\s*$~', $html)) {
      return $html;
    }
    $dom = Html::load($html);
    $xpath = new \DOMXPath($dom);
    $node = $xpath->query('//body/*')->item(0);
    $schema = [
      'type' => 'list',
      'prefix' => ($node->nodeName == 'ol') ? 'number' : 'bullet',
      'items' => [],
    ];
    foreach ($node->getElementsByTagName('li') as $item) {
      $item_value = '';
      $child_list = [];
      foreach ($item->childNodes as $child) {
        if (in_array($child->nodeName, ['ol', 'ul'])) {
          $item->removeChild($child);
          $child_list = [$this->convertHtmlListToSchema($child->ownerDocument->saveXML($child))];
        }
        else {
          $item_value .= $child->ownerDocument->saveXML($child);
        }
      }
      $item_value = trim($item_value);
      if (!empty($item_value)) {
        $schema['items'][] = $item_value;
      }
      if (!empty($child_list)) {
        $schema['items'][] = $child_list;
      }
    }
    return $schema;
  }
}
