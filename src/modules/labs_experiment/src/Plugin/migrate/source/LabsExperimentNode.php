<?php

/**
 * @file
 * Contains \Drupal\labs_experiment\Plugin\migrate\source\LabsExperimentNode.
 */

namespace Drupal\labs_experiment\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\migrate_plus\Plugin\migrate\source\Url;

/**
 * Source plugin for labs experiment content.
 *
 * @MigrateSource(
 *   id = "labs_experiment_node"
 * )
 */
class LabsExperimentNode extends Url {
  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    if ($value = $row->getSourceProperty('image')) {
      $row->setSourceProperty('countries', explode('|', $value));
    }
    return parent::prepareRow($row);
  }
}