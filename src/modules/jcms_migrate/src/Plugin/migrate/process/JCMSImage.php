<?php

namespace Drupal\jcms_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process the image field data into a D8 compatible image.
 *
 * @MigrateProcessPlugin(
 *   id = "jcms_image"
 * )
 */
class JCMSImage extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    list($image, $alt) = $value;

    if (!empty($image)) {
      if (strpos($image, 'public://') === 0) {
        $source = DRUPAL_ROOT . '/../scripts/legacy_cms_files/' . preg_replace('~^public://~', '', $image);
      }
      else {
        $source = drupal_get_path('module', 'jcms_migrate') . '/migration_assets/images/' . $image;
      }
      if (file_exists($source)) {
        $uri = file_unmanaged_copy($source, NULL, FILE_EXISTS_REPLACE);
        $file = \Drupal::entityTypeManager()->getStorage('file')->create(['uri' => $uri]);
        $file->save();
        return [
          'target_id' => $file->id(),
          'alt' => $alt ?: '',
        ];
      }
    }

    return NULL;
  }

}
