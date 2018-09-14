<?php

/**
 * @file
 * Drush commands for the module.
 */

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use eLife\ApiSdk\Model\Model;

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
  ];
}

/**
 * Callback function drush_jcms_migrate_jcms_transfer_content().
 */
function drush_jcms_migrate_jcms_transfer_content(int $nid) {
  $to_live = ((int) drush_get_option('to-live', 1)) === 0 ? FALSE : TRUE;
  $node = Node::load($nid);
  $node = \Drupal::service('jcms_admin.transfer_content')->transfer($node, $to_live);
  $node->save();
  drush_print(dt('Transfer complete!'));
}
