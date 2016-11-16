<?php

namespace Drupal\jcms_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * Source plugin for blog_article content.
 *
 * @MigrateSource(
 *   id = "jcms_blog_article_node"
 * )
 */
class JCMSBlogArticleNode extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('node', 'n')
      ->fields('n', ['nid', 'title', 'created', 'status']);
    $query->innerJoin('field_data_field_elife_n_category', 'category', 'category.entity_id = n.nid');
    $query->innerJoin('taxonomy_term_data', 'term', 'term.tid = category.field_elife_n_category_tid');
    $query->innerJoin('taxonomy_vocabulary', 'vocab', 'vocab.vid = term.vid');
    $query->innerJoin('field_data_field_elife_n_text', 'text' , 'text.entity_id = n.nid');
    $query->addField('text', 'field_elife_n_text_value', 'content');
    $query->addField('text', 'field_elife_n_text_summary', 'summary');

    $query->condition('vocab.machine_name', 'elife_n_category');
    $query->condition('term.name', ['early careers', 'events', 'news from eLife', 'in the news'], 'IN');
    $query->condition('n.title', 'Press package: %', 'NOT LIKE');
    $query->condition('n.type', 'elife_news_article');
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
      'title' => $this->t('Title'),
      'created' => $this->t('Created timestamp'),
      'status' => $this->t('Published'),
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
