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
      // Random values for orcid need to match the regex:
      // ^[0-9]{4}-[0-9]{4}-[0-9]{4}-[0-9]{3}[0-9X]$.
      case 'field_person_orcid':
        // Generate random string 0000-0000-0000-0000.
        $orcid = implode('-', array_map(function ($value) {
          return str_pad(rand(0, pow(10, $value) - 1), $value, '0', STR_PAD_LEFT);
        }, array_fill(0, 4, 4)));
        // Some orcid's may have a last character of X.
        $orcid = (rand(0, 10) === 0) ? substr($orcid, 0, -1) . 'X' : $orcid;
        return [
          'value' => $orcid,
        ];

      default:
        return parent::generateSampleValue($field_definition);
    }
  }

}
