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
    /** @var \Drupal\node\NodeInterface $node */
    foreach ($nodes as $node) {
      if ($type === 'person') {
        $details = Paragraph::load($people[$node->id()]->research_details_id);
        $swap_msa_values($details, 'field_research_expertises');
        $details->save();
      }
      else {
        $swap_msa_values($node, 'field_subjects');
      }
      if ($verbose) {
        // This is useful if content needs to be manually reassigned.
        drush_print(dt(':type (:uuid): :title (/node/!nid)', [
          ':type' => $node->bundle(),
          ':uuid' => substr($node->uuid(), -8),
          ':title' => $node->getTitle(),
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
