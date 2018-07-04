<?php

namespace Drupal\jcms_rest;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Site\Settings;
use Drupal\crop\Entity\Crop;
use function array_keys;
use function array_values;
use function str_replace;

/**
 * Helper methods for image uri IIIF paths.
 */
trait JCMSImageUriTrait {
  /**
   * Image sizes.
   *
   * @var array
   */
  protected $imageSizes = [
    'banner',
    'thumbnail',
  ];

  /**
   * Get the IIIF or web path to the image.
   */
  protected function processImageUri(string $image_uri, string $type = 'source', string $filemime = NULL) : string {
    $iiif = Settings::get('jcms_iiif_base_uri');
    if ($iiif) {
      $iiif_mount = Settings::get('jcms_iiif_mount', '/');
      $iiif_mount = trim($iiif_mount, '/');
      $iiif_mount .= (!empty($iiif_mount)) ? '/' : '';
      $image_uri = str_replace('public://' . $iiif_mount, '', $image_uri);
      $iiif_identifier = $this->encode($image_uri);
      if ($type == 'source') {
        switch ($filemime ?? \Drupal::service('file.mime_type.guesser')->guess($image_uri)) {
          case 'image/gif':
            $ext = 'gif';
            break;

          case 'image/png':
            $ext = 'png';
            break;

          default:
            $ext = 'jpg';
        }
        return $iiif . $iiif_identifier . '/full/full/0/default.' . $ext;
      }
      else {
        return $iiif . $iiif_identifier;
      }
    }
    else {
      return file_create_url($image_uri);
    }
  }

  /**
   * Process image field and return json string.
   */
  protected function processFieldImage(FieldItemListInterface $data, bool $required = FALSE, $size_types = ['banner', 'thumbnail'], $bump = FALSE) : array {
    if ($required || $data->count()) {
      $image = $this->getImageSizes($size_types);

      $image_uri = $data->first()->get('entity')->getTarget()->get('uri')->getString();
      $image_uri_info = $this->processImageUri($image_uri, 'info');
      $image_alt = (string) $data->first()->getValue()['alt'];
      $filemime = $data->first()->get('entity')->getTarget()->get('filemime')->getString();
      $filename = basename($image_uri);
      $width = (int) $data->first()->getValue()['width'];
      $height = (int) $data->first()->getValue()['height'];

      // @todo - elife - nlisgo - this is a temporary fix until we can trust mimetype of images.
      if (\Drupal::service('file.mime_type.guesser')->guess($image_uri) == 'image/png') {
        $filemime = 'image/jpeg';
        $filename = preg_replace('/\.png$/', '.jpg', $filename);
      }

      $image_uri_source = $this->processImageUri($image_uri, 'source', $filemime);
      foreach ($image as $type => $array) {
        $image[$type]['uri'] = $image_uri_info;
        $image[$type]['alt'] = $image_alt;
        $image[$type]['source'] = [
          'mediaType' => $filemime,
          'uri' => $image_uri_source,
          'filename' => $filename,
        ];
        $image[$type]['size'] = [
          'width' => $width,
          'height' => $height,
        ];

        // Focal point is optional.
        $crop_type = \Drupal::config('focal_point.settings')->get('crop_type');
        $crop = Crop::findCrop($image_uri, $crop_type);
        if ($crop) {
          $anchor = \Drupal::service('focal_point.manager')
            ->absoluteToRelative($crop->x->value, $crop->y->value, $image[$type]['size']['width'], $image[$type]['size']['height']);

          $image[$type]['focalPoint'] = $anchor;
        }
      }

      if ($bump && count($image) === 1) {
        $keys = array_keys($image);
        $image = $image[$keys[0]];
      }

      return $image;
    }

    return [];
  }

  /**
   * Get image sizes for the requested presets.
   */
  protected function getImageSizes($size_types = ['banner', 'thumbnail']) : array {
    $sizes = [];
    $size_types = (array) $size_types;
    foreach ($size_types as $size_type) {
      if (in_array($size_type, $this->imageSizes)) {
        $sizes[$size_type] = [];
      }
    }

    return $sizes;
  }

  /**
   * Percent encode IIIF component.
   */
  private function encode(string $string) : string {
    static $encoding = [
      '%' => '%25',
      '/' => '%2F',
      '?' => '%3F',
      '#' => '%23',
      '[' => '%5B',
      ']' => '%5D',
      '@' => '%40',
    ];

    return str_replace(array_keys($encoding), array_values($encoding), $string);
  }

}
