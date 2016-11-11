<?php

namespace Drupal\jcms_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process the collection content values into a the field_collection_content structure.
 *
 * @MigrateProcessPlugin(
 *   id = "jcms_split_collection_content"
 * )
 */
class JCMSSplitCollectionContent extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!empty($value)) {
      $items = explode('||', $value);
      $transformed_items = [];
      foreach ($items as $item) {
        if (preg_match('/^(?P<type>[^\|]+)\|(?P<source>[^\|]+)$/', $item, $match)) {
          $transformed_items[] = [
            'type' => $match['type'],
            'source' => $match['source'],
          ];
        }
      }

      if (!empty($transformed_items)) {
        return $transformed_items;
      }
    }

    return NULL;
  }

}
