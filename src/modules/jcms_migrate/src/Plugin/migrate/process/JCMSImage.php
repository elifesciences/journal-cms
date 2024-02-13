<?php

namespace Drupal\jcms_migrate\Plugin\migrate\process;

use Drupal\Core\File\FileSystemInterface;
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

  use JCMSGetRemoteFileTrait;

  /**
   * Store constructor row argument.
   *
   * @var \Drupal\migrate\Row
   */
  protected $row;

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $this->row = $row;
    [$image, $alt] = $value;
    $destination_path = $this->imagePath();
    $source = NULL;

    if (!empty($image)) {
      if (!preg_match('/^http/', $image)) {
        $source = \Drupal::service('extension.list.module')->getPath('jcms_migrate') . '/migration_assets/images/' . $image;
      }
      else {
        $source = $image;
      }

      /** @var \Drupal\Core\File\FileSystemInterface $file_system */
      $file_system = \Drupal::service('file_system');
      if (preg_match('/^http/', $source) && $data = $this->getFile($source)) {
        $new_filename = self::transliteration(basename($source));
        $file_system->prepareDirectory($destination_path, FileSystemInterface::CREATE_DIRECTORY);
        $file = \Drupal::service('file.repository')->writeData($data, $destination_path . $new_filename, FileSystemInterface::EXISTS_REPLACE);
      }
      elseif (file_exists($source)) {
        $file_system->prepareDirectory($destination_path, FileSystemInterface::CREATE_DIRECTORY);
        $new_filename = self::transliteration(basename($source));
        $uri = $file_system->copy($source, $destination_path . $new_filename, FileSystemInterface::EXISTS_REPLACE);
        $file = \Drupal::entityTypeManager()->getStorage('file')->create(['uri' => $uri]);
      }
      else {
        $file = NULL;
      }
      if (!empty($file)) {
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
   * Get stored row.
   */
  private function getRow() : Row {
    return $this->row;
  }

  /**
   * Derive image path from type and time.
   */
  private function imagePath(string $type = NULL, int $time = NULL) : string {
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

  /**
   * Transliterate and sanitize the string.
   */
  public static function transliteration(string $string) : string {
    $string = \Drupal::transliteration()->transliterate($string, LanguageInterface::LANGCODE_NOT_SPECIFIED, '');

    // Replace whitespace.
    $string = str_replace(' ', '_', $string);
    // Remove remaining unsafe characters.
    $string = preg_replace('![^0-9A-Za-z_.-]!', '', $string);
    // Remove multiple consecutive non-alphabetical characters.
    $string = preg_replace('/(_)_+|(\.)\.+|(-)-+/', '\\1\\2\\3', $string);
    // Force lowercase to prevent issues on case-insensitive file systems.
    $string = mb_strtolower($string);

    return $string;
  }

}
