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
    $split = $this->gatherTables(array_filter(preg_split('/\n+/', $html)));

    return array_map([$this, 'convertHtmlToSchema'], $split);
  }

  /**
   * Gather adjacent lines with tables.
   *
   * @param array $split
   * @return array
   */
  public function gatherTables(array $split) {
    $new_split = [];
    $table_found = [];
    foreach ($split as $item) {
      if (empty($table_found)) {
        if (strpos(trim($item), '<table') === 0) {
          $table_found[] = $item;
        }
        else {
          $new_split[] = $item;
        }
      }
      else {
        $table_found[] = $item;

      }
      if (!empty($table_found) && substr(trim($item), -8) === '</table>') {
        $new_split[] = implode('', $table_found);
        $table_found = [];
      }
    }

    return $new_split;
  }

  /**
   * Convert HTML on single line to schema structure.
   *
   * @param string $html
   * @return array|string
   */
  public function convertHtmlToSchema(string $html) {
    if (!preg_match('~^\s*<(table|ul|ol)[^>]*>.*</\1>\s*$~', $html)) {
      return $html;
    }
    $dom = Html::load($html);
    $xpath = new \DOMXPath($dom);
    $node = $xpath->query('//body/*')->item(0);

    if ($node->nodeName == 'table') {
      return [
        'type' => 'table',
        'tables' => [
          trim($html),
        ],
      ];
    }
    else {
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
            $child_list = [$this->convertHtmlToSchema($child->ownerDocument->saveXML($child))];
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
}
