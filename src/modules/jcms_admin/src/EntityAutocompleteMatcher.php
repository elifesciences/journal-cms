<?php

namespace Drupal\jcms_admin;

use Drupal\Core\Entity\EntityAutocompleteMatcher as CoreEntityAutocompleteMatcher;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Tags;

/**
 * Matcher class to get autocompletion results for entity reference.
 *
 * If operator CONTAINS used as matching operator and limit reached we favour
 * results found with operator STARTS_WITH.
 */
class EntityAutocompleteMatcher extends CoreEntityAutocompleteMatcher {

  /**
   * {@inheritdoc}
   */
  public function getMatches($target_type, $selection_handler, $selection_settings, $string = '') {
    $limit = 10;
    $matches = [];

    $options = $selection_settings + [
      'target_type' => $target_type,
      'handler' => $selection_handler,
    ];
    $handler = $this->selectionManager->getInstance($options);

    if (isset($string)) {
      // Get an array of matching entities.
      $match_operator = !empty($selection_settings['match_operator']) ? $selection_settings['match_operator'] : 'CONTAINS';
      $entity_labels = $handler->getReferenceableEntities($string, $match_operator, $limit + 1);

      // Loop through the entities and convert them into autocomplete output.
      foreach ($entity_labels as $values) {
        foreach ($values as $entity_id => $label) {
          $entity = \Drupal::entityTypeManager()->getStorage($target_type)->load($entity_id);
          $entity = \Drupal::entityManager()->getTranslationFromContext($entity);

          $info = '';
          if ($entity->getEntityType()->id() === 'node') {
            $info = ' [' . $entity->type->entity->label() . ', ' . ($entity->isPublished() ? 'Published' : 'Unpublished') . ']';
          }

          $key = "$label ($entity_id)";
          // Strip things like starting/trailing white spaces, line breaks and
          // tags.
          $key = preg_replace('/\s\s+/', ' ', str_replace("\n", '', trim(Html::decodeEntities(strip_tags($key)))));
          // Names containing commas or quotes must be wrapped in quotes.
          $key = Tags::encode($key);
          $label .= ' (' . $entity_id . ')' . $info;
          $matches[] = ['value' => $key, 'label' => $label];
        }
      }

      if (count($matches) > $limit && $match_operator === 'CONTAINS') {
        $matches = array_slice($matches, 0, $limit);
        $starts_with = $this->getMatches($target_type, $selection_handler, ['match_operator' => 'STARTS_WITH'] + $selection_settings, $string);
        if (count($starts_with) > 0) {
          foreach (array_reverse($starts_with, TRUE) as $item) {
            array_unshift($matches, $item);
          }
          array_unique($matches);
          $matches = array_slice($matches, 0, $limit);
        }
      }
    }

    return $matches;
  }

}
