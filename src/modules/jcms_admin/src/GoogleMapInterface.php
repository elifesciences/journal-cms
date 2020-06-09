<?php

namespace Drupal\jcms_admin;

/**
 * Interface for Google map.
 */
interface GoogleMapInterface {

  /**
   * Get ID from Google map URI.
   */
  public function getIdFromUri(string $uri): string;

  /**
   * Get title of Google map.
   */
  public function getTitle(string $id): string;

}
