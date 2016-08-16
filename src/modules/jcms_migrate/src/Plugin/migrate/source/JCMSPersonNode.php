<?php

namespace Drupal\jcms_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * Source plugin for perosn content.
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
      ->fields('n', ['nid', 'title', 'created', 'status']);
    $query->innerJoin('field_data_field_elife_pp_last_name', 'lname', 'lname.entity_id = n.nid');
    $query->innerJoin('field_data_field_elife_pp_first_name', 'fname', 'fname.entity_id = n.nid');
    // Don't migrate the early careers profiles, for now.
    $query->innerJoin('field_data_field_elife_pp_type', 'ptype', "ptype.entity_id = n.nid AND field_elife_pp_type_value NOT IN ('early-careers')");
    $query->leftJoin('field_data_field_elife_pp_orcid', 'orcid', 'orcid.entity_id = n.nid');
    $query->addExpression("CASE field_elife_pp_type_value WHEN 'deputy-editor' THEN 'leadership' WHEN 'editor-in-chief' THEN 'leadership' WHEN 'staff' THEN 'executive' ELSE field_elife_pp_type_value END", 'ptype');
    $query->addField('lname', 'field_elife_pp_last_name_value', 'name_last');
    $query->addExpression('SUBSTRING(TRIM(fname.field_elife_pp_first_name_value), 1, 1)', 'name_initial');
    $query->addField('fname', 'field_elife_pp_first_name_value', 'name_first');
    $query->addField('orcid', 'field_elife_pp_orcid_value', 'orcid_id');

    $query->condition('n.type', 'elife_person_profile');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'nid' => $this->t('Legacy ID'),
      'title' => $this->t('Name'),
      'created' => $this->t('Created timestamp'),
      'status' => $this->t('Published'),
      'ptype' => $this->t('Profile type'),
      'orcid_id' => $this->t('ORCID'),
      'person_id' => $this->t('Person ID'),
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

}
