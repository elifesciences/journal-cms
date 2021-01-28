<?php

/**
 * @file
 * Drush commands for the module.
 */

use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\taxonomy\Entity\Term;

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
    'create-person' => [
      'description' => 'Create a person.',
      'drupal dependencies' => ['jcms_migrate'],
      'arguments' => [
        'type' => 'Must be one of the existing options (e.g. reviewing-editor, senior-editor etc).',
        'surname' => 'Surname of person.',
      ],
      'options' => [
        'given' => 'Given names of person.',
        'email' => 'Email address of person.',
        'match' => 'Override current person if match found.',
        'upsert' => 'Create new or update existing.',
      ],
      'examples' => [
        'drush create-person reviewing-editor Jones' => 'Create a person of type reviewing-editor with surname Jones.',
      ],
    ],
    'resave-nodes' => [
      'description' => 'Resave all nodes of a certain type.',
      'drupal dependencies' => ['jcms_migrate'],
      'arguments' => [
        'type' => 'Content type.',
      ],
      'options' => [
        'limit' => 'Limit on the number of resaves to do.',
        'feedback' => 'Receive feedback after a specified number of resaves. (default: 100)',
      ],
      'examples' => [
        'drush resave-nodes blog_article' => 'Create a person of type reviewing-editor with surname Jones.',
        'drush resave-nodes person --limit=10' => 'Resave 10 nodes of content type person.',
        'drush resave-nodes blog_article --feedback=100' => 'Provide feedback for every 50 blog_articles resaved.',
      ],
    ],
    'amend-publish-date' => [
      'description' => 'Amend publish date of content.',
      'drupal dependencies' => ['jcms_migrate'],
      'aliases' => ['apd'],
      'arguments' => [
        'type' => 'Content type.',
        'id' => 'ID.',
        'date' => 'Date.',
      ],
      'examples' => [
        'drush amend-publish-date interview "09d713c1" "2019-09-26"' => 'Set the date for interview with ID "09d713c1" to "2019-09-26".',
      ],
    ],
  ];
}

/**
 * Callback function drush_jcms_admin_msa_switch().
 */
function drush_jcms_migrate_msa_switch(string $from, string $to = NULL) {
  // If $to is NULL then there is no need to trigger the save.
  $skip_save = (is_null($to)) ? TRUE : FALSE;
  // Allow $to to be null to force save of all content under this MSA.
  $to = $to ?? $from;

  $verbose = drush_get_context('DRUSH_VERBOSE');

  // Verify that $from and $to are recognised.
  if (is_numeric($from)) {
    $msa_from = [$from];
  }
  else {
    $msa_from = \Drupal::entityQuery('taxonomy_term')
      ->condition('vid', 'subjects')
      ->condition('field_subject_id.value', $from)
      ->execute();
  }

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

  $msa_from_id = function (int $tid) {
    static $msa = [];

    if (empty($msa[$tid])) {
      $term = Term::load($tid);
      $msa[$tid] = $term->get('field_subject_id')->getString();
    }

    return $msa[$tid];
  };

  $msa_from_ids = function (array $tids = []) use ($msa_from_id) {
    $msa = [];

    foreach ($tids as $tid) {
      $msa[$tid] = $msa_from_id($tid);
    }

    return $msa;
  };

  // Function to set new MSA values if $from and $to are different.
  $swap_msa_values = function (EntityInterface $entity, $field_name, &$old = [], &$new = []) use ($msa_from_target_id, $msa_to_target_id, $msa_from_ids) {
    if ($msa_from_target_id !== $msa_to_target_id || drush_get_context('DRUSH_VERBOSE')) {
      $values = $entity->get($field_name)->getValue();
      $new_values = [];
      foreach ($values as $k => $value) {
        $target_id = $value['target_id'];
        $old[$target_id] = TRUE;
        if ($target_id === $msa_from_target_id) {
          $target_id = $msa_to_target_id;
        }
        // Remove duplicates.
        if (!isset($new_values[$target_id])) {
          $new[$target_id] = TRUE;
          $new_values[$target_id] = [
            'target_id' => $target_id,
          ];
        }
      }
      $old = $msa_from_ids(array_keys($old));
      $new = $msa_from_ids(array_keys($new));
      if ($msa_from_target_id !== $msa_to_target_id) {
        $entity->set($field_name, array_values($new_values));
      }
    }
  };

  foreach ($content as $type => $nids) {
    $nodes = Node::loadMultiple($nids);
    drush_print(dt('Processing !count of type "!type"', [
      '!count' => count($nodes),
      '!type' => $type,
    ]));
    /** @var \Drupal\node\NodeInterface $node */
    foreach ($nodes as $node) {
      $old = [];
      $new = [];
      if ($type === 'person') {
        $details = Paragraph::load($people[$node->id()]->research_details_id);
        $swap_msa_values($details, 'field_research_expertises', $old, $new);
        $details->save();
      }
      else {
        $swap_msa_values($node, 'field_subjects', $old, $new);
      }
      if ($verbose) {
        // This is useful if content needs to be manually reassigned.
        drush_print(dt(':type (:uuid): :title from (":old" to ":new") (/node/!nid)', [
          ':type' => $node->bundle(),
          ':uuid' => substr($node->uuid(), -8),
          ':title' => $node->getTitle(),
          ':old' => implode(', ', $old),
          ':new' => implode(', ', $new),
          '!nid' => $node->id(),
        ]));
      }
      if (!$skip_save) {
        $node->save();
      }
    }
  }

  if (!$skip_save) {
    drush_print(dt('Switching complete!'));
  }
}

/**
 * Callback function drush_jcms_migrate_paragraphs_revisions_purge().
 */
function drush_jcms_migrate_paragraphs_revisions_purge(string $field = NULL) {
  $logger = \Drupal::logger('jcms_message_import');
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
  $logger->info('Number of revisions to be deleted', ['count' => $count]);

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
      elseif ($limit > 0) {
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
            \Drupal::entityTypeManager()->getStorage('paragraph')->deleteRevision($revision_id);
            $count_deleted++;
            if (($count_deleted % $feedback_frequency) == 0) {
              drush_print(dt('Number of revisions deleted is !count.', ['!count' => $count_deleted]));
              $logger->info('Progress of revisions deleted.', ['count' => $count_deleted]);
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
    } while ($limit >= 0);
    drush_print(dt('Total number of revisions deleted is !count.', ['!count' => $count_deleted]));
    $logger->info('Completed deletion of revisions', ['count' => $count_deleted]);
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
    $logger->info('Completed with some revisions left in place.', ['remaining' => $count]);
  }
  else {
    $field = !empty($field) ? $field : 'all fields';
    drush_print(dt('Success! All revisions for !field have been purged.', ['!field' => $field]));
    $logger->info('Success! All revisions have been purged.', ['field' => $field]);
  }
}

/**
 * Callback function drush_jcms_migrate_paragraphs_revisions_optimise().
 */
function drush_jcms_migrate_paragraphs_revisions_optimise() {
  $logger = \Drupal::logger('jcms_message_import');
  drush_print(dt('Optimise paragraph revision tables.'));
  $logger->info('Optimise paragraph revision tables.');
  $table_list = Database::getConnection()->query("SHOW TABLES LIKE 'paragraph_r%'")->fetchCol();
  $table_list[] = 'paragraphs_item_revision';
  $table_list[] = 'paragraphs_item_revision_field_data';
  Database::getConnection()->query('OPTIMIZE TABLE ' . implode(',', $table_list));
  drush_print(dt('All paragraph revision tables have been optimised.'));
  $logger->info('All paragraph revision tables have been optimised.');
}

/**
 * Callback function drush_jcms_migrate_create_person().
 */
function drush_jcms_migrate_create_person(string $type, string $surname) {
  $given = !is_bool(drush_get_option('given')) ? trim(drush_get_option('given')) : NULL;
  $email = !is_bool(drush_get_option('email')) ? trim(drush_get_option('email')) : NULL;
  $match = drush_get_option('match');
  $upsert = (!$match && drush_get_option('upsert'));
  $person = NULL;

  if ($match || $upsert) {
    $query = \Drupal::entityQuery('node')
      ->condition('field_person_name_surname.value', $surname)
      ->condition('field_person_type.value', $type);

    if ($given) {
      $query
        ->condition('field_person_name_given.value', $given . '%', 'LIKE');
    }

    if ($result = $query->execute()) {
      $person = Node::load(array_shift($result));
    }
  }

  if ((!$match && !$upsert) || ($upsert && is_null($person))) {
    /** @var \Drupal\node\Entity\NodeInterface $person */
    $person = Node::create([
      'type' => 'person',
      'title' => $surname,
    ]);

    $person->field_person_type = [
      [
        'value' => $type,
      ],
    ];

    $person->field_person_name_surname = [
      [
        'value' => $surname,
      ],
    ];

    if ($given) {
      $person->field_person_name_given = [
        [
          'value' => $given,
        ],
      ];
    }

    $person->setOwnerId(1);
    $person->setPublished();
  }

  if ($person instanceof NodeInterface) {
    if ($email) {
      $person->field_person_email = [
        [
          'value' => $email,
        ],
      ];
    }

    $person->save();
    drush_print(dt($person->label() . ' (' . $type . ') - ' . ($person->isNew() ? 'created' : 'updated') . '.'));
  }
  elseif ($match) {
    drush_print(dt(implode(' ', array_filter([$given, $surname])) . ' (' . $type . ') - not found.'));
  }
}

/**
 * Callback function drush_jcms_migrate_resave_nodes().
 */
function drush_jcms_migrate_resave_nodes(string $type) {
  $limit = drush_get_option('limit') ? (int) drush_get_option('limit') : 0;
  $feedback_frequency = drush_get_option('feedback') ? (int) drush_get_option('feedback') : 100;

  $query = \Drupal::entityQuery('node')
    ->condition('type', $type);

  if ($limit > 0) {
    $query->range(0, $limit);
  }

  $nids = $query->execute();

  if ($nids) {
    $count = count($nids);
    drush_print(dt('Number of !type nodes to be resaved is !count.', ['!type' => $type, '!count' => count($nids)]));
    if ($feedback_frequency > $count) {
      $feedback_frequency = $count;
    }

    for ($i = 0; $i < $count; $i += $feedback_frequency) {
      $nodes = Node::loadMultiple(array_slice($nids, $i, $feedback_frequency));
      foreach ($nodes as $node) {
        $node->save();
      }
      drush_print(dt('Number of !type nodes resaved is !count.', ['!type' => $type, '!count' => $i + count($nodes)]));
    }
  }
}

/**
 * Callback function drush_jcms_migrate_amend_publish_date().
 */
function drush_jcms_migrate_amend_publish_date(string $type, string $id, string $date) {
  $content = [
    $id => $date,
  ];

  $query = \Drupal::entityQuery('node')
    ->condition('uuid', '(' . implode('|', array_keys($content)) . ')', 'REGEXP')
    ->condition('type', $type);

  $nids = $query->execute();
  if ($nids) {
    $nodes = Node::loadMultiple($nids);
    foreach ($nodes as $node) {
      $id = substr($node->uuid(), -8);
      $current_date = $node->getCreatedTime();
      $new_date = strtotime($content[$id] . ' ' . date('H:i:s', $current_date));
      $node->set('created', $new_date);
      $node->save();
      drush_print(dt('!type (!uuid): "!title" publish date changed from "!current-date" to "!new-date" (/node/!nid)', [
        '!type' => $node->bundle(),
        '!uuid' => substr($node->uuid(), -8),
        '!title' => trim($node->getTitle()),
        '!current-date' => date('Y-m-d', $current_date),
        '!new-date' => date('Y-m-d', $new_date),
        '!nid' => $node->id(),
      ]));
    }
  }
}
