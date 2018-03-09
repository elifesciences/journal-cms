<?php

namespace Drupal\jcms_rest\Plugin\Field\FieldTypeExtend;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\TimestampItem as TimestampItemExtend;

/**
 * {@inheritdoc}
 */
class TimestampItem extends TimestampItemExtend {

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    switch ($field_definition->getName()) {
      case 'field_job_advert_closing_date':
        $now = time();
        return [
          'value' => rand($now, $now + 60 * 60 * 24 * 30),
        ];

      default:
        return parent::generateSampleValue($field_definition);
    }
  }

}
