<?php

namespace Drupal\jcms_admin;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Tags;

class EntityAutocompleteMatcher extends \Drupal\Core\Entity\EntityAutocompleteMatcher {

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
          $key = "$label ($entity_id)";
          // Strip things like starting/trailing white spaces, line breaks and
          // tags.
          $key = preg_replace('/\s\s+/', ' ', str_replace("\n", '', trim(Html::decodeEntities(strip_tags($key)))));
          // Names containing commas or quotes must be wrapped in quotes.
          $key = Tags::encode($key);
          $matches[] = ['value' => $key, 'label' => $label];
        }
      }

      if (count($matches) > $limit && $match_operator === 'CONTAINS') {
        $matches = array_slice($matches, 0, $limit);
        $values = [];
        foreach ($matches as $match) {
          $values[] = $match['value'];
        }
        $starts_with = parent::getMatches($target_type, $selection_handler, ['match_operator' => 'STARTS_WITH'] + $selection_settings, $string);
        if (count($starts_with) > 0) {
          foreach ($starts_with as $item) {
            if (!in_array($item['value'], $values)) {
              array_unshift($matches, $item);
            }
          }
          $matches = array_slice($matches, 0, $limit);
        }
      }
    }

    return $matches;
  }

}
