<?php

namespace Drupal\jcms_rest;

/**
 * Class PathMediaTypeMapper.
 *
 * @package Drupal\jcms_rest
 */
class PathMediaTypeMapper {

  /**
   * An array of media types, keyed by their appropriate paths.
   *
   * @return array
   *   An array of media types.
   */
  protected function getMappings() : array {
    $map = [
      '/labs-posts' => 'application/vnd.elife.labs-post-list+json',
      '/labs-posts/{number}' => 'application/vnd.elife.labs-post+json',
      '/people' => 'application/vnd.elife.person-list+json',
      '/people/{id}' => 'application/vnd.elife.person+json',
      '/podcast-episodes' => 'application/vnd.elife.podcast-episode-list+json',
      '/podcast-episodes/{number}' => 'application/vnd.elife.podcast-episode+json',
      '/subjects' => 'application/vnd.elife.subject-list+json',
      '/subjects/{id}' => 'application/vnd.elife.subject+json',
      '/blog-articles' => 'application/vnd.elife.blog-article-list+json',
      '/blog-articles/{id}' => 'application/vnd.elife.blog-article+json',
      '/interviews' => 'application/vnd.elife.interview-list+json',
      '/interviews/{id}' => 'application/vnd.elife.interview+json',
      '/annual-reports' => 'application/vnd.elife.annual-report-list+json',
      '/annual-reports/{year}' => 'application/vnd.elife.annual-report+json',
      '/events' => 'application/vnd.elife.event-list+json',
      '/events/{year}' => 'application/vnd.elife.event+json',
      '/collections' => 'application/vnd.elife.collection-list+json',
      '/collections/{id}' => 'application/vnd.elife.collection+json',
      '/press-packages' => 'application/vnd.elife.press-package-list+json',
      '/press-packages/{id}' => 'application/vnd.elife.press-package+json',
      '/community' => 'application/vnd.elife.community-list+json',
      '/covers' => 'application/vnd.elife.cover-list+json',
      '/covers/current' => 'application/vnd.elife.cover-list+json',
      '/highlights/{list}' => 'application/vnd.elife.highlight-list+json',
    ];
    return $map;
  }

  /**
   * Takes a path and returns the appropriate media type if it exists.
   *
   * @param string $path
   *   A path/URI such as /subjects.
   *
   * @return string
   *   A media type or an empty string if no matching media type is found.
   */
  public function getMediaTypeByPath(string $path) : string {
    // Trim any trailing slashes from the path.
    $path = rtrim($path, '/');
    $mappings = $this->getMappings();
    if (array_key_exists($path, $mappings)) {
      return $mappings[$path];
    }
    else {
      foreach ($mappings as $map_path => $media_type) {
        $match = $this->matchPathWithPlaceholders($path, $map_path);
        if (!empty($match) && $match == $path) {
          return $media_type;
        }
      }
    }
    return '';
  }

  /**
   * Takes the current path and a path with placeholders and generates a path.
   *
   * @param string $path
   *   A path/URI such as /subjects.
   * @param string $map_path
   *   A path with placeholders such as /subjects/{id}.
   *
   * @return string
   *   A generated path from the placeholder string, or an empty string.
   */
  protected function matchPathWithPlaceholders(string $path, string $map_path) : string {
    $generated_path = '';
    // Parse the map path into a regex and build a list of placeholders.
    $placeholders = [];
    $regex = preg_replace_callback('#/({[a-z]+})(?=/|$)#', function ($x) use (&$placeholders) {
      $placeholders[] = $x[1];
      return '/([^/]+)';
    }, $map_path);
    // If this path doesn't have placeholders.
    if ($map_path == $regex) {
      return $generated_path;
    }
    // Check the path against the regex and populate variables if it matches.
    $values = [];
    if (preg_match("#^$regex$#", $path, $matches)) {
      foreach ($placeholders as $id => $placeholder) {
        $values[$placeholder] = $matches[$id + 1];
      }
      // Replace the placeholders with %s then replace those with the values.
      $map_path = preg_replace("#{[a-z]+}#", '%s', $map_path);
      $generated_path = vsprintf($map_path, $values);
    }
    return $generated_path;
  }

}
