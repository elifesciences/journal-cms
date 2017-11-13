<?php

namespace Drupal\jcms_rest\Plugin\Field\FieldTypeExtend;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem as EntityReferenceItemExtend;

/**
 * {@inheritdoc}
 */
class EntityReferenceItem extends EntityReferenceItemExtend {

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $values = parent::generateSampleValue($field_definition);
    switch ($field_definition->getName()) {
      // Random values must be unique.
      case 'field_subjects':
        static $unique = [];
        // 25 subject terms are generated and the maximum number associated with content is 3.
        if (count($unique) === 10) {
          $unique = array_slice(array_values($unique), -2);
        }
        while (in_array($values['target_id'], $unique)) {
          $values = parent::generateSampleValue($field_definition);
        }
        $unique[] = $values['target_id'];
        break;
    }
    return $values;
  }

}
