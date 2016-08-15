<?php

namespace Drupal\jcms_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Source plugin for beer content.
 *
 * @MigrateSource(
 *   id = "jcms_person_node"
 * )
 */
class JCMSPersonNode extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('node', 'n')
      ->fields('n', ['nid', 'title', 'created'])
      ->condition('type', 'elife_person_profile');
    $query->join('field_data_field_elife_pp_last_name', 'lname', 'lname.entity_id = n.nid');
    $query->join('field_data_field_elife_pp_first_name', 'fname', 'fname.entity_id = n.nid');
    $query->addExpression("CONCAT(fname.field_elife_pp_first_name_value, ' ', lname.field_elife_pp_last_name_value)", 'preferred_name');
    $query->addExpression("CONCAT(lname.field_elife_pp_last_name_value, ', ', fname.field_elife_pp_first_name_value)", 'index_name');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'nid' => $this->t('Person ID'),
      'title' => $this->t('Name'),
      'created' => $this->t('Created date'),
      'preferred_name' => $this->t('Preferred name'),
      'index_name' => $this->t('Index name'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'nid' => [
        'type' => 'integer',
        'alias' => 'n',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    return parent::prepareRow($row);
  }

}
