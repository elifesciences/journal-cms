<?php

/**
 * @file
 * Contain \Drupal\labs_experiment\migrate\process\FileImport.
 */

namespace Drupal\labs_experiment\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Example on how to migrate an image from any place in Drupal.
 *
 * @MigrateProcessPlugin(
 *   id = "file_import"
 * )
 */
class FileImport extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty($value)) {
      return [];
    }

    $source = drupal_get_path('module', 'labs_experiment') . '/migration_assets/images/' . $value;

    if (!$uri = file_unmanaged_copy($source)) {
      return [];
    }

    $file = \Drupal::entityTypeManager()->getStorage('file')->create(['uri' => $uri]);
    $file->save();

    return $file->id();
  }
}