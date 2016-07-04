<?php

namespace Drupal\labs_experiment\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process the image field data into a D8 compatible image.
 *
 * @MigrateProcessPlugin(
 *   id = "labs_image"
 * )
 */
class LabsImage extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    list($image, $image_alt) = $value;

    if (!empty($image) && !empty($image_alt)) {
      $source = drupal_get_path('module', 'labs_experiment') . '/migration_assets/images/' . $image;
      if ($uri = file_unmanaged_copy($source)) {
        $file = \Drupal::entityTypeManager()->getStorage('file')->create(['uri' => $uri]);
        $file->save();
        return [
          'target_id' => $file->id(),
          'alt' => $image_alt,
        ];
      }
    }

    return NULL;
  }

}