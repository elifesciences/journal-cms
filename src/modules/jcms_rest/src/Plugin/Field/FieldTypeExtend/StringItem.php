<?php

namespace Drupal\jcms_rest\Plugin\Field\FieldTypeExtend;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\StringItem as StringItemExtend;

/**
 * {@inheritdoc}
 */
class StringItem extends StringItemExtend {

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    switch ($field_definition->getName()) {
      // Random values for orcid need to match the regex ^[0-9]{4}-[0-9]{4}-[0-9]{4}-[0-9]{3}[0-9X]$.
      case 'field_person_orcid':
        $orcid = [4, 4, 4, 3];
        array_walk($orcid, function (&$value) {
          $value = str_pad(rand(0, pow(10, $value-1)-1), $value, '0', STR_PAD_LEFT);
        });
        return [
          'value' => implode('-', $orcid) . substr(implode('', range(0, 9)) . 'X', rand(0, 10), 1),
        ];
        break;
      default:
        return parent::generateSampleValue($field_definition);
    }
  }

}
