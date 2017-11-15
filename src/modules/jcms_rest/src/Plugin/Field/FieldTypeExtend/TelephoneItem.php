<?php

namespace Drupal\jcms_rest\Plugin\Field\FieldTypeExtend;

use Drupal\telephone\Plugin\Field\FieldType\TelephoneItem as TelephoneItemExtend;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * {@inheritdoc}
 */
class TelephoneItem extends TelephoneItemExtend {

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $values = parent::generateSampleValue($field_definition);
    switch ($field_definition->getName()) {
      case 'field_block_phone_number':
          $values['value'] = '+'.$values['value'];
          break;
    }
    return $values;
  }

}
