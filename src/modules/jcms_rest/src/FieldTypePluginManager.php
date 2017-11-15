<?php

namespace Drupal\jcms_rest;

use Drupal\Core\Field\FieldTypePluginManager as FieldTypePluginManagerExtend;
use Drupal\jcms_rest\Plugin\Field\FieldTypeExtend\EntityReferenceItem;
use Drupal\jcms_rest\Plugin\Field\FieldTypeExtend\IntegerItem;
use Drupal\jcms_rest\Plugin\Field\FieldTypeExtend\StringItem;
use Drupal\jcms_rest\Plugin\Field\FieldTypeExtend\TelephoneItem;
use Drupal\jcms_rest\Plugin\Field\FieldTypeExtend\TimestampItem;

/**
 * {@inheritdoc}
 */
class FieldTypePluginManager extends FieldTypePluginManagerExtend {

  /**
   * {@inheritdoc}
   */
  public function getPluginClass($type) {
    $plugin_class = parent::getPluginClass($type);
    if ($type == 'entity_reference') {
      // Override class for EntityReferenceItem.
      $plugin_class = EntityReferenceItem::class;
    }
    if ($type == 'integer') {
      // Override class for IntegerItem.
      $plugin_class = IntegerItem::class;
    }
    if ($type == 'string') {
      // Override class for StringItem.
      $plugin_class = StringItem::class;
    }
    if ($type == 'timestamp') {
      // Override class for TimestampItem.
      $plugin_class = TimestampItem::class;
    }
    if ($type == 'telephone') {
      // Override class for TelephoneItem.
      $plugin_class = TelephoneItem::class;
    }
    return $plugin_class;
  }

}
