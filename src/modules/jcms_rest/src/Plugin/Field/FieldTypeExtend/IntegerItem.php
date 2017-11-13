<?php

namespace Drupal\jcms_rest\Plugin\Field\FieldTypeExtend;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\IntegerItem as IntegerItemExtend;

/**
 * {@inheritdoc}
 */
class IntegerItem extends IntegerItemExtend {

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $values = parent::generateSampleValue($field_definition);
    switch ($field_definition->getName()) {
      // Random values must be unique.
      case 'field_annual_report_year':
        static $unique = [];
        while (in_array($values['value'], $unique)) {
          $values = parent::generateSampleValue($field_definition);
        }
        $unique[] = $values['value'];
        break;
    }
    return $values;
  }

}
