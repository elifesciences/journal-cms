<?php

namespace Drupal\jcms_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * Source plugin for interview content.
 *
 * @MigrateSource(
 *   id = "jcms_interview_node"
 * )
 */
class JCMSInterviewNode extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('node', 'n')
      ->fields('n', ['nid', 'uid', 'title', 'created', 'changed', 'status', 'uuid']);
    $query->innerJoin('field_data_field_elife_i_first_name', 'first_name', 'first_name.entity_id = n.nid');
    $query->innerJoin('field_data_field_elife_i_last_name', 'last_name', 'last_name.entity_id = n.nid');
    $query->innerJoin('field_data_field_elife_i_text', 'text' , 'text.entity_id = n.nid');
    $query->leftJoin('field_data_field_elife_i_cv', 'cv', 'cv.entity_id = n.nid');
    $query->leftJoin('field_data_field_elife_i_cv_date', 'cv_date', 'cv_date.entity_id = cv.field_elife_i_cv_value');
    $query->leftJoin('field_data_field_elife_i_cv_text', 'cv_text', 'cv_text.entity_id = cv.field_elife_i_cv_value');
    $query->addExpression("GROUP_CONCAT(cv_date.field_elife_i_cv_date_value ORDER BY cv.delta ASC SEPARATOR '|')", 'cv_dates');
    $query->addExpression("GROUP_CONCAT(cv_text.field_elife_i_cv_text_value ORDER BY cv.delta ASC SEPARATOR '|')", 'cv_texts');
    $query->addField('first_name', 'field_elife_i_first_name_value', 'name_first');
    $query->addField('last_name', 'field_elife_i_last_name_value', 'name_last');
    $query->addField('text', 'field_elife_i_text_value', 'content');
    $query->addField('text', 'field_elife_i_text_summary', 'summary');
    $query->groupBy('cv.entity_id');
    $query->groupBy('n.nid');
    $query->groupBy('first_name.field_elife_i_first_name_value');
    $query->groupBy('last_name.field_elife_i_last_name_value');
    $query->groupBy('text.field_elife_i_text_value');
    $query->groupBy('text.field_elife_i_text_summary');

    $query->condition('n.type', 'elife_early_careers_interview');
    $query->condition('n.status', NODE_PUBLISHED);

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'nid' => $this->t('Legacy ID'),
      'uid' => $this->t('Author ID'),
      'uuid' => $this->t('UUID'),
      'title' => $this->t('Title'),
      'created' => $this->t('Created timestamp'),
      'changed' => $this->t('Changed timestamp'),
      'status' => $this->t('Published'),
      'first_name' => $this->t('First name'),
      'last_name' => $this->t('Last name'),
      'cv_dates' => $this->t('CV dates'),
      'cv_texts' => $this->t('CV texts'),
      'summary' => $this->t('Summary'),
      'content' => $this->t('Content'),
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
