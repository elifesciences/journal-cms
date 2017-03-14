<?php

namespace Drupal\jcms_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process the role by source the rid values to assign.
 *
 * @MigrateProcessPlugin(
 *   id = "jcms_role"
 * )
 */
class JCMSRole extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    switch ($value) {
      case 'eLife Editor':
        return 'editor';
        break;
      case 'eLife Administrator':
        return 'administrator';
        break;
      default:
        return NULL;
    }
  }

}
