<?php

/**
 * @file
 * Drush commands for the module.
 */

use Drupal\node\Entity\Node;

/**
 * Implements hook_drush_command().
 */
function jcms_admin_drush_command() {
  return [
    'jcms-transfer-content' => [
      'description' => 'Transfer HTML from content to content preview or vice versa.',
      'drupal dependencies' => ['jcms_admin'],
      'arguments' => [
        'id' => 'Node id of content to perform transfer.',
      ],
      'options' => [
        'to-live' => 'Transfer from preview to live (default: true).',
      ],
      'examples' => [
        'drush jcms-transfer-content 123' => 'Transfer HTML from content to content preview for node with ID 123.',
      ],
    ],
    'jcms-profile-orcid' => [
      'description' => 'update a profile with the orcid ID',
      'drupal dependencies' => ['jcms_admin'],
      'arguments' => [
        'id' => 'ID of profile.',
        'orcid' => 'ORCID ID',
      ],
      'examples' => [
        'drush jcms-profile-orcid 3ec1c7f1 0000-0001-8615-6409' => 'Set the ORCID ID to 0000-0001-8615-6409 for the profile with ID 3ec1c7f1',
      ],
    ],
    'jcms-profile-notify' => [
      'description' => 'trigger re-save of profiles',
      'drupal dependencies' => ['jcms_admin'],
      'options' => [
        'focus' => 'filter by research focus',
        'organism' => 'filter by research organism',
      ],
      'examples' => [
        'drush jcms-profile-notify' => 'Re-save all profiles to trigger notifications',
        'drush jcms-profile-notify --focus="<i>Sulfolobus</i>"' => 'Re-save all profiles to trigger notifications with focus "<i>Sulfolobus</i>"',
        'drush jcms-profile-notify --organism="amphibians"' => 'Re-save all profiles to trigger notifications with organism "amphibians"',
      ],
    ],
    'jcms-detect-duplicate-organisms' => [
      'description' => 'detect duplicate research organism entries',
      'drupal dependencies' => ['jcms_admin'],
      'examples' => [
        'drush jcms-detect-duplicate-organisms' => 'Outputs a list of research organisms that are duplicate entries in the database.',
      ],
    ],
    'jcms-detect-duplicate-focuses' => [
      'description' => 'detect duplicate research focus entries',
      'drupal dependencies' => ['jcms_admin'],
      'examples' => [
        'drush jcms-detect-duplicate-organisms' => 'Outputs a list of research focuses that are duplicate entries in the database.',
      ],
    ],
  ];
}

/**
 * Callback function drush_jcms_admin_jcms_transfer_content().
 */
function drush_jcms_admin_jcms_transfer_content(int $nid) {
  $to_live = ((int) drush_get_option('to-live', 1)) === 0 ? FALSE : TRUE;
  $node = Node::load($nid);
  $node = \Drupal::service('jcms_admin.transfer_content')->transfer($node, $to_live);
  $node->save();
  drush_print(dt('Transfer complete!'));
}

/**
 * Callback function drush_jcms_admin_jcms_profile_orcid().
 */
function drush_jcms_admin_jcms_profile_orcid(string $id, string $orcid) {
  if (!preg_match('/^0000\-000(1\-[5-9]|2\-[0-9]|3\-[0-4])\d{3}\-\d{3}[\dX]$/', $orcid)) {
    drush_print(dt('Invalid ORCID ID detected: :orcid', [':orcid' => $orcid]));
  }
  else {
    $query = \Drupal::entityQuery('node')
      ->condition('changed', \Drupal::time()->getRequestTime(), '<')
      ->condition('type', 'person')
      ->condition('uuid', '%' . $id, 'LIKE');

    $nids = $query->execute();
    if ($nids) {
      $nid = reset($nids);
      $node = Node::load($nid);
      $node->set('field_person_orcid', $orcid);
      $node->save();
      drush_print(dt('Profile updated! (:id, ":title", :orcid)', [
        ':id' => $id,
        ':title' => $node->getTitle(),
        ':orcid' => $orcid,
      ]));
    }
    else {
      drush_print(dt('No profile found: (:id)', [':id' => $id]));
    }
  }
}

/**
 * Callback function drush_jcms_admin_jcms_profile_notify().
 */
function drush_jcms_admin_jcms_profile_notify() {
  $verbose = drush_get_context('DRUSH_VERBOSE');
  $focus = drush_get_option('focus');
  $organism = drush_get_option('organism');

  foreach (['focus', 'organism'] as $i) {
    if (!is_string(${$i}) || empty(${$i})) {
      ${$i} = NULL;
    }
  }

  $profiles = [];

  $load_profiles = function ($type, $value) {
    $tid = \Drupal::entityQuery('taxonomy_term')
      ->condition('vid', $type)
      ->condition('name', $value)
      ->execute();

    return !empty($tid) ? _jcms_admin_profiles_from_focuses_organisms($type, current($tid)) : [];
  };

  if ($focus) {
    $profiles = $load_profiles('research_focuses', $focus);
  }
  elseif ($organism) {
    $profiles = $load_profiles('research_organisms', $organism);
  }
  else {
    $people = \Drupal::entityQuery('node')
      ->condition('type', 'person')
      ->execute();
    if ($people) {
      $profiles = array_values($people);
    }
  }

  $nodes = Node::loadMultiple($profiles);
  if (!empty($nodes)) {
    drush_print(dt('Processing !count profile(s)', [
      '!count' => count($nodes),
    ]));
    foreach ($nodes as $node) {
      if ($verbose) {
        // This is useful if content needs to be manually reassigned.
        drush_print(dt(':type: :title (/node/!nid)', [
          ':type' => $node->bundle(),
          ':title' => $node->getTitle(),
          '!nid' => $node->id(),
        ]));
      }
      $node->setChangedTime(\Drupal::time()->getRequestTime());
      $node->save();
    }
  }
}

/**
 * Callback function drush_jcms_admin_jcms_detect_duplicate_organisms().
 */
function drush_jcms_admin_jcms_detect_duplicate_organisms() {
  drush_print(_jcms_admin_term_duplicates_csv(_jcms_admin_term_duplicates('research_organisms')));
}

/**
 * Callback function drush_jcms_admin_jcms_detect_duplicate_focuses().
 */
function drush_jcms_admin_jcms_detect_duplicate_focuses() {
  drush_print(_jcms_admin_term_duplicates_csv(_jcms_admin_term_duplicates('research_focuses')));
}

/**
 * Convert term duplicates array to csv output.
 */
function _jcms_admin_term_duplicates_csv(array $dups) {
  $output = [
    implode(',', [
      '"id"',
      '"duplicate group"',
      '"raw"',
      '"clean"',
      '"profile ids"',
      '"profile labels"',
      '"profile count"',
    ]),
  ];

  foreach ($dups as $k => $group) {
    foreach ($group as $tid => $item) {
      $output[] = implode(',', [
        $tid,
        '"' . $k . '"',
        '"' . $item['raw'] . '"',
        '"' . $item['label'] . '"',
        '"' . $item['profile_ids'] . '"',
        '"' . $item['profile_labels'] . '"',
        $item['profile_count'],
      ]);
    }
  }

  return implode(PHP_EOL, $output);
}

/**
 * Get array of term data from vid.
 */
function _jcms_admin_term_duplicates(string $vid) {
  $tids = \Drupal::entityQuery('taxonomy_term')
    ->condition('vid', $vid)
    ->execute();
  $controller = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
  $terms = $controller->loadMultiple($tids);
  $dups = [];
  foreach ($terms as $term) {
    $clean_label = preg_replace('/(>|<)\s+/', '$1', trim($term->label()));
    $lower = strtolower($clean_label);
    $dups[$lower][$term->id()] = [
      'raw' => $term->label(),
      'label' => $clean_label,
    ];
  }

  foreach ($dups as $k => $dup) {
    if (count($dup) > 1) {
      foreach ($dup as $tid => $data) {
        $people = _jcms_admin_profiles_from_focuses_organisms($vid, $tid);
        $profile_labels = [];
        if ($people) {
          $profiles = Node::loadMultiple($people);
          foreach ($profiles as $profile) {
            $profile_labels[] = $profile->label() . ' (' . $profile->id() . ')';
          }
        }

        $dups[$k][$tid] = $data + [
          'profile_ids' => $people ? implode(',', $people) : '',
          'profile_labels' => $profile_labels ? implode('|', $profile_labels) : '',
          'profile_count' => count($people),
        ];
      }
    }
    else {
      unset($dups[$k]);
    }
  }

  return $dups;
}
