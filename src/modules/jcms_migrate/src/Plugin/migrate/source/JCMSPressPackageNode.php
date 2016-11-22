<?php

namespace Drupal\jcms_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * Source plugin for press_package content.
 *
 * @MigrateSource(
 *   id = "jcms_press_package_node"
 * )
 */
class JCMSPressPackageNode extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('node', 'n')
      ->fields('n', ['nid', 'title', 'created', 'status']);
    $query->innerJoin('field_data_field_elife_n_text', 'text' , 'text.entity_id = n.nid');
    $query->addExpression("SUBSTRING_INDEX(n.title, ': ', -1)", 'press_title');
    $query->addField('text', 'field_elife_n_text_value', 'content');

    $query->condition('n.title', 'Press package: %', 'LIKE');
    $query->condition('n.type', 'elife_news_article');

    // @todo - elife - nlisgo - we may need to create json fixtures for a few
    // e.g. https://elifesciences.org/elife-news/press-package-new-species-human-relative-discovered-south-african-cave (nid: 250425)
    // $query->condition('n.nid', [250425], 'NOT IN');

    $query->groupBy('n.nid');
    $query->groupBy('text.field_elife_n_text_value');
    $query->groupBy('text.field_elife_n_text_summary');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'nid' => $this->t('Legacy ID'),
      'press_title' => $this->t('Title'),
      'created' => $this->t('Created timestamp'),
      'status' => $this->t('Published'),
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
