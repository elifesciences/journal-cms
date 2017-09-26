<?php

namespace Drupal\jcms_rest;

use Drupal\Core\Field\FieldTypePluginManager as FieldTypePluginManagerExtend;
use Drupal\jcms_rest\Plugin\Field\FieldTypeExtend\StringItem;

/**
 * {@inheritdoc}
 */
class FieldTypePluginManager extends FieldTypePluginManagerExtend {

  /**
   * {@inheritdoc}
   */
  public function getPluginClass($type) {
    $plugin_class = parent::getPluginClass($type);
    if ($type == 'string') {
      // Override class for StringItem.
      $plugin_class = StringItem::class;
    }
    return $plugin_class;
  }

}
