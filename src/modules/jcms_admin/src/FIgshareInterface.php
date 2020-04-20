<?php

namespace Drupal\jcms_admin;

/**
 * Interface for Figshare.
 */
interface FigshareInterface {

  /**
   * Get ID from Figshare URI.
   */
  public function getIdFromUri(string $uri): string;

  /**
   * Get title of Figshare.
   */
  public function getTitle(string $id): string;

}
