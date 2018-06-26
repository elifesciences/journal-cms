<?php

namespace Drupal\jcms_rest\Plugin\Field\FieldTypeExtend;

use Drupal\telephone\Plugin\Field\FieldType\TelephoneItem as TelephoneItemExtend;
use Drupal\Core\Field\FieldDefinitionInterface;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\RegionCode;

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
        $phoneNumberUtil = PhoneNumberUtil::getInstance();
        $number = $phoneNumberUtil->getExampleNumber(RegionCode::GB);
        $values['value'] = $phoneNumberUtil->format($number, PhoneNumberFormat::E164);
        break;
    }
    return $values;
  }

}
