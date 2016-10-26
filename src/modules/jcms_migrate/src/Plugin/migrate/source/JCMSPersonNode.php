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
    $query->leftJoin('field_data_field_elife_title', 'focus_term', "focus_term.entity_type = 'taxonomy_term' AND focus_term.entity_id = focus.field_elife_pp_research_focus_target_id");
    $query->leftJoin('field_data_field_elife_pp_organism', 'organism', 'organism.entity_id = n.nid');
    $query->leftJoin('field_data_field_elife_title', 'organism_term', "organism_term.entity_type = 'taxonomy_term' AND organism_term.entity_id = organism.field_elife_pp_organism_target_id");
    $query->leftJoin('field_data_field_elife_pp_profile', 'profile', 'profile.entity_id = n.nid');
    $query->leftJoin('field_data_field_elife_pp_interest', 'interest', 'interest.entity_id = n.nid');
    $query->leftJoin('field_data_field_elife_pp_photo', 'photo', 'photo.entity_id = n.nid');
    $query->leftJoin('file_managed', 'fm', 'fm.fid = photo.field_elife_pp_photo_fid');
    $query->addExpression("GROUP_CONCAT(DISTINCT expertise_term.name ORDER BY expertise.delta ASC SEPARATOR '|')", 'expertises');
    $query->addExpression("GROUP_CONCAT(DISTINCT focus_term.field_elife_title_value ORDER BY focus.delta ASC SEPARATOR '|')", 'focuses');
    $query->addExpression("GROUP_CONCAT(DISTINCT organism_term.field_elife_title_value ORDER BY organism.delta ASC SEPARATOR '|')", 'organisms');
    $query->addExpression("CASE ptype.field_elife_pp_type_value WHEN 'deputy-editor' THEN 'leadership' WHEN 'editor-in-chief' THEN 'leadership' WHEN 'staff' THEN 'executive' WHEN 'directors' THEN 'director' ELSE ptype.field_elife_pp_type_value END", 'ptype');
    $query->addField('lname', 'field_elife_pp_last_name_value', 'name_last');
    $query->addExpression('SUBSTRING(TRIM(fname.field_elife_pp_first_name_value), 1, 1)', 'name_initial');
    $query->addField('fname', 'field_elife_pp_first_name_value', 'name_first');
    $query->addField('orcid', 'field_elife_pp_orcid_value', 'orcid_id');
    $query->addField('profile', 'field_elife_pp_profile_value', 'profile_description');
    $query->addField('interest', 'field_elife_pp_interest_value', 'interest_value');
    $query->addField('fm', 'uri', 'photo_uri');
    $query->addExpression("CONCAT(fname.field_elife_pp_first_name_value, ' ', lname.field_elife_pp_last_name_value)", 'photo_alt');

    $query->condition('n.type', 'elife_person_profile');
    $query->groupBy('n.nid');
    $query->groupBy('lname.field_elife_pp_last_name_value');
    $query->groupBy('fname.field_elife_pp_first_name_value');
    $query->groupBy('orcid.field_elife_pp_orcid_value');
    $query->groupBy('ptype.field_elife_pp_type_value');
    $query->groupBy('profile.field_elife_pp_profile_value');
    $query->groupBy('interest.field_elife_pp_interest_value');
    $query->groupBy('fm.fid');

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
      'focuses' => $this->t('Research focuses'),
      'organisms' => $this->t('Research organisms'),
      'profile_description' => $this->t('Profile description'),
      'interest_value' => $this->t('Competing interest'),
      'photo_uri' => $this->t('Photo URI'),
      'photo_alt' => $this->t('Photo alt'),
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
