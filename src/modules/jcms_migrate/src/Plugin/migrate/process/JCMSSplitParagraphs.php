<?php

namespace Drupal\jcms_migrate\Plugin\migrate\process;

use DOMDocument;
use DOMXPath;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process the content values into paragraphs.
 *
 * @MigrateProcessPlugin(
 *   id = "jcms_split_paragraphs"
 * )
 */
class JCMSSplitParagraphs extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!empty($value)) {
      $dom = new DomDocument();
      $dom->loadHTML('<?xml encoding="UTF-8"><html><body>' . $value . '</body></html>');
      $xpath = new DomXPath($dom);
      $children = $xpath->query('//body/*');
      $paragraphs = [];
      foreach ($children as $child) {
        $paragraphs[] = [
          'type' => 'paragraph',
          'text' => $dom->saveHTML($child),
        ];
      }
      return $paragraphs;
    }

    return NULL;
  }

}