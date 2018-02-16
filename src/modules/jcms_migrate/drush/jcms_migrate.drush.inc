<?php

/**
 * @file
 * Drush commands for the module.
 */

use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityInterface;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Implements hook_drush_command().
 */
function jcms_migrate_drush_command() {
  return [
    'msa-switch' => [
      'description' => 'Switch all content from one major subject area to another.',
      'drupal dependencies' => ['jcms_migrate'],
      'aliases' => ['msa-s'],
      'arguments' => [
        'from' => 'MSA to switch from.',
        'to' => 'MSA to switch from.',
      ],
      'examples' => [
        'drush msa-switch biochemistry biochemistry-chemical-biology' => 'Switch content from biochemistry to biochemistry-chemical-biology.',
      ],
    ],
    'paragraphs-revisions-purge' => [
      'description' => 'Purges unused paragraph revisions for the given field.',
      'drupal dependencies' => ['jcms_migrate'],
      'aliases' => ['pr-purge'],
      'arguments' => [
        'field' => 'Field name.',
      ],
      'options' => [
        'limit' => 'Limit on the number of deletions to do.',
        'feedback' => 'Receive feedback after a specified number of deletions. (default: 100)',
      ],  
      'examples' => [
        'drush paragraphs-revisions-purge field_article_json' => 'Purge all unused paragraph revisions for the field field_article_json.',
        'drush paragraphs-revisions-purge' => 'Purge all unused paragraph revisions on any field.',
        'drush paragraphs-revisions-purge --limit=1000' => 'Purge 1000 unused paragraph revisions on any field.',
        'drush paragraphs-revisions-purge --feedback=100' => 'Provide feedback for every 100 unused paragraph revisions purged.',
      ],
    ],
    'paragraphs-revisions-optimise' => [
      'description' => "Optimise paragraph revision tables. Don't run until after purge.",
      'drupal dependencies' => ['jcms_migrate'],
      'aliases' => ['pr-optimise'],
      'examples' => [
        'drush paragraphs-revisions-optimise' => 'Optimise all paragraph revision tables.',
      ],
    ],
  ];
}

/**
 * Callback function drush_jcms_admin_msa_switch().
 */
function drush_jcms_migrate_msa_switch(string $from, string $to = NULL) {
  // Allow $to to be null to force save of all content under this MSA.
  $to = $to ?? $from;

  // Verify that $from and $to are recognised.
  $msa_from = \Drupal::entityQuery('taxonomy_term')
    ->condition('vid', 'subjects')
    ->condition('field_subject_id.value', $from)
    ->execute();
  if (!$msa_from) {
    return drush_set_error('jcms_migrate', dt('!from is not a recognised major subject area.', ['!from' => $from]));
  }
  $msa_to = \Drupal::entityQuery('taxonomy_term')
    ->condition('vid', 'subjects')
    ->condition('field_subject_id.value', $to)
    ->execute();
  if (!$msa_to) {
    return drush_set_error('jcms_migrate', dt('!to is not a recognised major subject area.', ['!to' => $to]));
  }
  drush_print(dt('Switch content from !from to !to.', [
    '!from' => $from,
    '!to' => $to,
  ]));

  // Retrieve target_id values for $from and $to.
  $msa_from_target_id = current($msa_from);
  $msa_to_target_id = current($msa_to);
  $content = [];

  // Gather all of type blog_article, collection and podcast_episode for $from.
  foreach (['blog_article', 'collection', 'podcast_episode'] as $type) {
    $result = \Drupal::entityQuery('node')
      ->condition('field_subjects.target_id', $msa_from_target_id, 'IN')
      ->condition('type', $type)
      ->execute();
    if ($result) {
      $content[$type] = array_values($result);
    }
  }

  // Gather all of type person for $from.
  $research_details = Database::getConnection()->select('node__field_research_details', 'rd');
  $research_details->addField('rd', 'entity_id', 'person_nid');
  $research_details->addField('rd', 'field_research_details_target_id', 'research_details_id');
  $research_details->innerJoin('paragraph__field_research_expertises', 're', 're.entity_id = rd.field_research_details_target_id');
  $research_details->condition('re.field_research_expertises_target_id', $msa_from_target_id);
  $research_details->condition('rd.bundle', 'person');
  $people = $research_details->execute()->fetchAllAssoc('person_nid');
  if ($people) {
    $content['person'] = array_keys($people);
  }

  // Function to set new MSA values if $from and $to are different.
  $swap_msa_values = function (EntityInterface $entity, $field_name) use ($msa_from_target_id, $msa_to_target_id) {
    if ($msa_from_target_id !== $msa_to_target_id) {
      $values = $entity->get($field_name)->getValue();
      $new_values = [];
      foreach ($values as $k => $value) {
        $target_id = $value['target_id'];
        if ($target_id === $msa_from_target_id) {
          $target_id = $msa_to_target_id;
        }
        // Remove duplicates.
        if (!isset($new_values[$target_id])) {
          $new_values[$target_id] = [
            'target_id' => $target_id,
          ];
        }
      }
      $entity->set($field_name, array_values($new_values));
    }
  };

  foreach ($content as $type => $nids) {
    $nodes = Node::loadMultiple($nids);
    drush_print(dt('Processing !count of type "!type"', [
      '!count' => count($nodes),
      '!type' => $type,
    ]));
    foreach ($nodes as $node) {
      if ($type === 'person') {
        $details = Paragraph::load($people[$node->id()]->research_details_id);
        $swap_msa_values($details, 'field_research_expertises');
        $details->save();
      }
      else {
        $swap_msa_values($node, 'field_subjects');
      }
      $node->save();
    }
  }

  drush_print(dt('Switching complete!'));
}

/**
 * Callback function drush_jcms_migrate_paragraphs_revisions_purge().
 */
function drush_jcms_migrate_paragraphs_revisions_purge(string $field = NULL) {
  $limit = drush_get_option('limit') ? (int) drush_get_option('limit') : 0;
  $feedback_frequency = drush_get_option('feedback') ? (int) drush_get_option('feedback') : 100;
  
  // Count number of paragraph revisions that are not a default revision.
  $query = Database::getConnection()->select('paragraphs_item_revision', 'pir');
  if (!empty($field) && Database::getConnection()->schema()->tableExists('node__' . $field)) {
    $query->join('node__' . $field, 'nf', 'nf.' . $field . '_target_id=pir.id');
  }
  $query->leftjoin('paragraphs_item', 'pi', 'pi.revision_id=pir.revision_id');
  $query->isNull('pi.id');
  $count = $query->countQuery()->execute()->fetch()->expression;
  drush_print(dt('Number of revisions to be deleted is !count.', ['!count' => $count]));
  
  if ($count > 0 && $limit >= 0) {
    $count_deleted = 0;
    do {
      $query = Database::getConnection()->select('paragraphs_item_revision', 'pir');
      if (!empty($field)) {
        $query->join('node__' . $field, 'nf', 'nf.' . $field . '_target_id=pir.id');
      }
      $query->leftjoin('paragraphs_item', 'pi', 'pi.revision_id=pir.revision_id');
      $query->isNull('pi.id');
      $query->addField('pir', 'revision_id');
      if ($limit > $feedback_frequency) {
        $query->range(0, $feedback_frequency);
        $limit -= $feedback_frequency;
      }
      else if ($limit > 0) {
        $query->range(0, $limit);
        $limit = -1;
      }
      else {
        $query->range(0, $feedback_frequency);
      }
      $revision_ids = $query->execute()->fetchCol();
      if ($revision_ids) {
        foreach ($revision_ids as $revision_id) {
          try {
//            \Drupal::entityTypeManager()->getStorage('paragraph')->deleteRevision($revision_id);
            $count_deleted++;
            if (($count_deleted % $feedback_frequency) == 0) {
              drush_print(dt('Number of revisions deleted is !count.', ['!count' => $count_deleted]));
            }
          }
          catch (\Exception $e) {
            // This should not be reached but is a precaution against attempts
            // to delete default revision which would thrown an exception.
          }
        }
      }
      else {
        // No more revisions to delete.
        $limit = -1;
        break;
      }
    } while($limit >= 0);
    drush_print(dt('Total number of revisions deleted is !count.', ['!count' => $count_deleted]));
  }

  // Get the number of revisions left to delete.
  $query = Database::getConnection()->select('paragraphs_item_revision', 'pir');
  if (!empty($field) && Database::getConnection()->schema()->tableExists('node__' . $field)) {
    $query->join('node__' . $field, 'nf', 'nf.' . $field . '_target_id=pir.id');
  }
  $query->leftjoin('paragraphs_item', 'pi', 'pi.revision_id=pir.revision_id');
  $query->isNull('pi.id');
  $count = $query->countQuery()->execute()->fetch()->expression;
  if ($count > 0) {
    drush_print(dt('Completed with !count revisions left in place.', ['!count' => $count]));
  }
  else {
    drush_print(dt('Success! All revisions for !field have been purged.', ['!field' => !empty($field) ? $field : 'all fields']));
  }
}

/**
 * Callback function drush_jcms_migrate_paragraphs_revisions_optimise().
 */
function drush_jcms_migrate_paragraphs_revisions_optimise() {
  drush_print(dt('Optimise paragraph revision tables.'));
  $table_list = Database::getConnection()->query("SHOW TABLES LIKE 'paragraph_r%'")->fetchCol();
  $table_list[] = 'paragraphs_item_revision';
  $table_list[] = 'paragraphs_item_revision_field_data';
  Database::getConnection()->query('OPTIMIZE TABLE '.implode(',', $table_list));
  drush_print(dt('All paragraph revision tables have been optimised.'));
}
