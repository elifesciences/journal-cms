<?php

namespace Drupal\jcms_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * Source plugin for collection content.
 *
 * @MigrateSource(
 *   id = "jcms_collection_node"
 * )
 */
class JCMSCollectionNode extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('node', 'n')
      ->fields('n', ['nid', 'uid', 'title', 'created', 'changed', 'status', 'uuid']);
    $query->leftJoin('field_data_field_elife_c_image', 'photo', 'photo.entity_id = n.nid');
    $query->leftJoin('file_managed', 'fm', 'fm.fid = photo.field_elife_c_image_fid');
    $query->leftJoin('field_data_field_elife_c_text', 'impact', 'impact.entity_id = n.nid');
    $query->leftJoin('field_data_field_elife_c_curators', 'curators', 'curators.entity_id = n.nid');
    $query->innerJoin('field_data_field_elife_c_articles', 'articles', 'articles.entity_id = n.nid');
    $query->innerJoin('node', 'an', 'an.nid = articles.field_elife_c_articles_target_id');
    $query->leftJoin('field_data_field_elife_c_related', 'related', 'related.entity_id = n.nid');
    $query->leftJoin('node', 'rn', 'rn.nid = related.field_elife_c_related_target_id');
    $query->addField('impact', 'field_elife_c_text_value', 'summary');
    $query->addField('fm', 'uri', 'photo_uri');
    $query->addExpression("GROUP_CONCAT(DISTINCT curators.field_elife_c_curators_target_id ORDER BY curators.delta ASC SEPARATOR '|')", 'curators');
    $query->addExpression("GROUP_CONCAT(DISTINCT IF(an.type = 'elife_news_article', CONCAT('blog_article|', an.nid), IF(an.type = 'elife_article_reference', CONCAT('article|', SUBSTRING_INDEX(an.title, ':', 1)), an.type)) ORDER BY articles.delta SEPARATOR '||')", "content");
    $query->addExpression("GROUP_CONCAT(DISTINCT SUBSTRING_INDEX(rn.title, ':', 1) ORDER BY related.delta ASC)", 'related');

    $query->condition('n.type', 'elife_collection');
    $query->condition('n.status', NODE_PUBLISHED);
    $query->groupBy('n.nid');
    $query->groupBy('curators.entity_id');
    $query->groupBy('impact.field_elife_c_text_value');
    $query->groupBy('fm.fid');

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
      'title' => $this->t('Name'),
      'created' => $this->t('Created timestamp'),
      'changed' => $this->t('Changed timestamp'),
      'status' => $this->t('Published'),
      'summary' => $this->t('Summary'),
      'photo_uri' => $this->t('Photo URI'),
      'curators' => $this->t('Curators'),
      'content' => $this->t('Collection content'),
      'related' => $this->t('Related'),
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
