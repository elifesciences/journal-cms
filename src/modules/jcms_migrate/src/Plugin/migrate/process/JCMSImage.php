<?php

namespace Drupal\jcms_migrate\Plugin\migrate\process;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Language\LanguageInterface;
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
   * @var \Drupal\migrate\Row
   */
  protected $row;

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $this->row = $row;
    list($image, $alt) = $value;
    $destination_path = $this->imagePath();
    $row_source = $row->getSource();
    $source = NULL;

    // Allow cover images to be drawn from the migration_assets/images/covers folder.
    if ($row_source['plugin'] == 'jcms_cover_node' && !empty($row_source['related'])) {
      $related = json_decode('{' . $row_source['related'] . '}', TRUE);
      if ($related['type'] == 'article') {
        $images = glob(drupal_get_path('module', 'jcms_migrate') . '/migration_assets/images/covers/' . $related['source'] . '-*');
        if (!empty($images)) {
          $source = reset($images);
        }
      }
    }

    if (!empty($image) || !empty($source)) {
      if (empty($source)) {
        if (strpos($image, 'public://') === 0) {
          $source = DRUPAL_ROOT . '/../scripts/legacy_cms_files/' . preg_replace('~^public://~', '', $image);
        }
        else {
          $source = drupal_get_path('module', 'jcms_migrate') . '/migration_assets/images/' . $image;
        }
      }

      if (file_exists($source)) {
        file_prepare_directory($destination_path, FILE_CREATE_DIRECTORY);
        $new_filename = self::transliteration(basename($source));
        $uri = file_unmanaged_copy($source, $destination_path . $new_filename, FILE_EXISTS_REPLACE);
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

  /**
   * @return \Drupal\migrate\Row
   */
  private function getRow() {
    return $this->row;
  }

  private function imagePath($type = NULL, $time = NULL) {
    $destination = $this->getRow()->getDestination();
    if (!$type) {
      $type = (!empty($destination['vid'])) ? $destination['vid'] : $destination['type'];
    }
    if (!$time) {
      $time = (!empty($destination['created'])) ? $destination['created'] : time();
    }
    $folder = $type . '/' . date('Y-m', $time) . '/';
    return 'public://iiif/' . $folder;
  }

  public static function transliteration($string) {
    // Transliterate and sanitize the string.
    $string = \Drupal::transliteration()->transliterate($string, LanguageInterface::LANGCODE_NOT_SPECIFIED, '');

    // Replace whitespace.
    $string = str_replace(' ', '_', $string);
    // Remove remaining unsafe characters.
    $string = preg_replace('![^0-9A-Za-z_.-]!', '', $string);
    // Remove multiple consecutive non-alphabetical characters.
    $string = preg_replace('/(_)_+|(\.)\.+|(-)-+/', '\\1\\2\\3', $string);
    // Force lowercase to prevent issues on case-insensitive file systems.
    $string = Unicode::strtolower($string);

    return $string;
  }

}
