<?php

namespace Drupal\jcms_admin;

/**
 * Interface for YouTube.
 */
interface YouTubeInterface {

  /**
   * Get ID from YouTube URI.
   */
  public function getIdFromUri(string $uri);

  /**
   * Get dimensions of YouTube video.
   */
  public function getDimensions(string $id);

}
