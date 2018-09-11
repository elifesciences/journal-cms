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
        /*
         * As field_subjects has a cardinality of unlimited this method may be
         * called between 1 and 3 times for each content item generated.
         *
         * @see: \Drupal\devel_generate\DevelGenerateBase::populateFields
         * if ($cardinality == FieldStorage...
         *   $max = rand(1, 3);
         * }
         *
         * In order to avoid duplicates, we should ensure that 25 or more
         * subject terms are available (./scripts/generate_content.sh).
         *
         * When this method is called it could be the 1st, 2nd or 3rd value for
         * the content.
         * To ensure the 3rd item is unique for the content we must ensure it
         * doesn't match the previous 2 values retrieved.
         */
        if (count($unique) === 10) {
          $unique = array_slice(array_values($unique), -2);
        }
        while (in_array($values['target_id'] ?? [], $unique)) {
          $values = parent::generateSampleValue($field_definition);
        }
        $unique[] = $values['target_id'];
        break;
    }
    return $values;
  }

}
