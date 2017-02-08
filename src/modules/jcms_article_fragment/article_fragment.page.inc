<?php

/**
 * @file
 * Contains article_fragment.page.inc.
 *
 * Page callback for Article fragment entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Article fragment templates.
 *
 * Default template: article_fragment.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_article_fragment(array &$variables) {
  // Fetch ArticleFragment Entity Object.
  $article_fragment = $variables['elements']['#article_fragment'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
