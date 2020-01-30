<?php

/**
 * @file
 * Drush commands for the module.
 */

use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

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
    'jcms-profile-list' => [
      'description' => 'list of profiles (SE and BRE) with organisms and focuses',
      'drupal dependencies' => ['jcms_admin'],
      'examples' => [
        'drush jcms-profile-lis' => 'Outputs a list of senior editors and BRE with associated research organisms and focuses.',
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
 * Callback function drush_jcms_admin_jcms_profile_list().
 */
function drush_jcms_admin_jcms_profile_list() {
  drush_print(_jcms_admin_profile_list_csv(_jcms_admin_profile_list()));
}

/**
 * Convert profile list array to csv output.
 */
function _jcms_admin_profile_list_csv(array $profiles) {
  $output = [
    implode(',', [
      '"profile id"',
      '"profile label"',
      '"profile type"',
      '"research organisms"',
      '"research focuses"',
      '"archived"',
      '"published"',
    ]),
  ];

  foreach ($profiles as $nid => $profile) {
    $output[] = implode(',', [
      $nid,
      '"' . $profile['profile_label'] . '"',
      '"' . $profile['profile_type'] . '"',
      !empty($profile['organisms']) ? '"' . implode('|', $profile['organisms']) . '"' : '',
      !empty($profile['focuses']) ? '"' . implode('|', $profile['focuses']) . '"' : '',
      $profile['archived'],
      $profile['published'],
    ]);
  }

  return implode(PHP_EOL, $output);
}

/**
 * Get array of senior editor and BRE profiles.
 *
 * With associated research organisms and focuses.
 */
function _jcms_admin_profile_list($types = ['senior-editor', 'reviewing-editor']) {
  $nids = \Drupal::entityQuery('node')
    ->condition('changed', \Drupal::time()->getRequestTime(), '<')
    ->condition('type', 'person')
    ->condition('field_person_type.value', $types, 'IN')
    ->execute();
  $nodes = Node::loadMultiple($nids);

  $list = [];

  foreach ($nodes as $nid => $node) {
    $details = json_decode($node->get('field_research_details_json')->getString(), TRUE);
    $list[$nid] = [
      'profile_label' => $node->get('field_person_preferred_name')->count() ? $node->get('field_person_preferred_name')->getString() : implode(' ', array_filter([$node->get('field_person_name_given')->getString(), $node->get('field_person_name_surname')->getString()])),
      'profile_type' => $node->get('field_person_type')->getString(),
      'organisms' => !empty($details['organisms']) ? $details['organisms'] : [],
      'focuses' => !empty($details['focuses']) ? $details['focuses'] : [],
      'archived' => $node->get('field_archive')->getString(),
      'published' => $node->isPublished() ? 1 : 0,
    ];
  }

  return $list;
}
