<?php

namespace Drupal\jcms_admin;

/**
 * Interface for Tweet.
 */
interface TweetInterface {

  /**
   * Get ID from Tweet status URI.
   */
  public function getIdFromUri(string $uri);

  /**
   * Get details of Tweet.
   */
  public function getDetails(string $id);

}
