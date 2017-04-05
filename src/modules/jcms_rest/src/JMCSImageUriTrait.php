<?php

namespace Drupal\jcms_rest;

use Drupal\Core\Site\Settings;

trait JMCSImageUriTrait {

  /**
   * Get the IIIF or web path to the image.
   *
   * @param string $image_uri
   * @param string $type
   * @param null|string $filemime
   * @return string
   */
  protected function processImageUri($image_uri, $type = 'source', $filemime = NULL) {
    $iiif = Settings::get('jcms_iiif_base_uri');
    if ($iiif) {
      $iiif_mount = Settings::get('jcms_iiif_mount', '/');
      $iiif_mount = trim($iiif_mount, '/');
      $iiif_mount .= (!empty($iiif_mount)) ? '/' : '';
      $image_uri = str_replace('public://' . $iiif_mount, '', $image_uri);
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
        return $iiif . $image_uri . '/full/full/0/default.' . $ext;
      }
      else {
        return $iiif . $image_uri;
      }
    }
    else {
      return file_create_url($image_uri);
    }
  }
}
