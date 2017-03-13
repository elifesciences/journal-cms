<?php

namespace Drupal\jcms_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Source plugin for cover content.
 *
 * @MigrateSource(
 *   id = "jcms_cover_node"
 * )
 */
class JCMSCoverNode extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('node', 'n')
      ->fields('n', ['nid', 'title', 'created', 'status', 'uuid']);
    $query->innerJoin('field_data_field_elife_fm_reference', 'ref', 'ref.entity_id = n.nid');
    $query->innerJoin('node', 'an', 'an.nid = ref.field_elife_fm_reference_target_id');
    $query->innerJoin('field_data_field_elife_image', 'im', 'im.entity_id = n.nid');
    $query->innerJoin('file_managed', 'fm', 'fm.fid = im.field_elife_image_fid');
    $query->leftJoin('field_data_field_elife_p_episode_number', 'en', 'en.entity_id = an.nid');
    $query->leftJoin('field_data_field_elife_a_article_id', 'aid', 'aid.entity_id = an.nid');
    $query->condition('n.type', 'elife_cover');
    $query->condition('n.status', NODE_PUBLISHED);
    $query->addField('fm', 'uri', 'photo_uri');
    $query->addField('im', 'field_elife_image_alt', 'photo_alt');
    $query->addExpression("CASE an.type WHEN 'elife_article_reference' THEN CONCAT('\"type\": \"article\", \"source\": \"', aid.field_elife_a_article_id_value, '\"') WHEN 'elife_podcast' THEN CONCAT('\"type\": \"podcast_episode\", \"source\": \"', en.field_elife_p_episode_number_value, '\"') WHEN 'elife_news_article' THEN CONCAT('\"type\": \"blog_article\", \"source\": \"', an.nid, '\"') ELSE '' END", 'related');
    $query->orderBy('n.created', 'DESC');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'nid' => $this->t('Legacy ID'),
      'uuid' => $this->t('UUID'),
      'title' => $this->t('Title'),
      'created' => $this->t('Created timestamp'),
      'status' => $this->t('Published'),
      'photo_uri' => $this->t('Photo URI'),
      'photo_alt' => $this->t('Photo alt'),
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

  /**
   * (@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $row->setDestinationProperty('counter', $this->rowNo());
    return parent::prepareRow($row);
  }

  protected function rowNo() {
    static $co = 0;
    $co++;
    return $co;
  }

}
