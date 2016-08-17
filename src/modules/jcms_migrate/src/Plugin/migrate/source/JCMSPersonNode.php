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
    $query->leftJoin('field_data_field_elife_pp_expertise', 'expertise', 'expertise.entity_id = n.nid');
    $query->leftJoin('taxonomy_term_data', 'expertise_term', 'expertise_term.tid = expertise.field_elife_pp_expertise_target_id');
    $query->leftJoin('field_data_field_elife_pp_research_focus', 'focus', 'focus.entity_id = n.nid');
    $query->leftJoin('field_data_field_elife_pp_organism', 'organism', 'organism.entity_id = n.nid');
    $query->addExpression('GROUP_CONCAT(DISTINCT expertise_term.name ORDER BY expertise.delta ASC)', 'expertises');
    $query->addExpression('GROUP_CONCAT(DISTINCT focus.field_elife_pp_research_focus_target_id ORDER BY focus.delta ASC)', 'focus_ids');
    $query->addExpression('GROUP_CONCAT(DISTINCT organism.field_elife_pp_organism_target_id ORDER BY organism.delta ASC)', 'organism_ids');
    $query->addExpression("CASE ptype.field_elife_pp_type_value WHEN 'deputy-editor' THEN 'leadership' WHEN 'editor-in-chief' THEN 'leadership' WHEN 'staff' THEN 'executive' ELSE ptype.field_elife_pp_type_value END", 'ptype');
    $query->addField('lname', 'field_elife_pp_last_name_value', 'name_last');
    $query->addExpression('SUBSTRING(TRIM(fname.field_elife_pp_first_name_value), 1, 1)', 'name_initial');
    $query->addField('fname', 'field_elife_pp_first_name_value', 'name_first');
    $query->addField('orcid', 'field_elife_pp_orcid_value', 'orcid_id');

    $query->condition('n.type', 'elife_person_profile');
    $query->groupBy('n.nid');
    $query->groupBy('lname.field_elife_pp_last_name_value');
    $query->groupBy('fname.field_elife_pp_first_name_value');
    $query->groupBy('orcid.field_elife_pp_orcid_value');
    $query->groupBy('ptype.field_elife_pp_type_value');

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
      'expertises' => $this->t('Subjects of expertise'),
      'focus_ids' => $this->t('Research Focus IDs'),
      'organism_ids' => $this->t('Organism Focus IDs'),
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
