<?php

namespace Drupal\jcms_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Source plugin for research organism terms.
 *
 * @MigrateSource(
 *   id = "jcms_research_organism_term"
 * )
 */
class JCMSResearchOrganismTerm extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('taxonomy_term_data', 'ttd')
      ->fields('ttd', ['tid', 'name']);
    $query->innerJoin('taxonomy_vocabulary', 'tv', 'tv.vid = ttd.vid');

    $query->condition('tv.machine_name', 'elife_pp_experimental_organism');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'tid' => $this->t('Legacy ID'),
      'name' => $this->t('Term'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'tid' => [
        'type' => 'integer',
        'alias' => 'ttd',
      ],
    ];
  }

}
