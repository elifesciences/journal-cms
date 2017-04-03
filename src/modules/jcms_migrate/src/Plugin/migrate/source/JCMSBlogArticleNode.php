<?php

namespace Drupal\jcms_migrate\Plugin\migrate\source;

use Drupal\Core\Database\Query\Condition;
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
   * @var array
   */
  protected $terms = ['early careers', 'events', 'news from eLife', 'in the news'];

  /**
   * @var bool
   */
  protected $nullTerms = TRUE;

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('node', 'n')
      ->fields('n', ['nid', 'uid', 'title', 'created', 'status', 'uuid']);
    $query->leftJoin('field_data_field_elife_n_category', 'category', 'category.entity_id = n.nid');
    $query->leftJoin('taxonomy_term_data', 'term', 'term.tid = category.field_elife_n_category_tid');
    $query->leftJoin('taxonomy_vocabulary', 'vocab', "vocab.vid = term.vid AND vocab.machine_name = 'elife_n_category'");
    $query->leftJoin('field_data_field_elife_n_text', 'text' , 'text.entity_id = n.nid');
    $query->addField('text', 'field_elife_n_text_value', 'content');
    $query->addField('text', 'field_elife_n_text_summary', 'summary');

    $db_or = new Condition('OR');
    $db_or->condition('term.name', $this->terms, 'IN');
    if ($this->nullTerms) {
      // Some source articles having not been assigned a category, should we assume they need to be migrated here?
      $db_or->isNull('term.name');
    }
    $query->condition($db_or);
    $query->condition('n.title', 'Press package: %', 'NOT LIKE');
    $query->condition('n.type', 'elife_news_article');
    $query->condition('n.status', NODE_PUBLISHED);
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
      'uid' => $this->t('Author ID'),
      'uuid' => $this->t('UUID'),
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
