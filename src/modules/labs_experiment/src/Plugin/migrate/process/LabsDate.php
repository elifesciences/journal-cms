<?php

namespace Drupal\labs_experiment\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process the date values.
 *
 * @MigrateProcessPlugin(
 *   id = "transform_date"
 * )
 */
class LabsDate extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!empty($value)) {
      return strtotime($value);
    }

    return NULL;
  }

}