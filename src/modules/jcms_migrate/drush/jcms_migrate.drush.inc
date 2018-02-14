<?php

/**
 * @file
 * Drush commands for the module.
 */

/**
 * Implements hook_drush_command().
 */
function jcms_migrate_drush_command() {
  $items = [];
  $items['paragraphs-revisions-delete'] = [
    'description' => 'Deletes unused paragraph revisions for the given field.',
    'drupal dependencies' => [],
    'aliases' => ['prd'],
    'arguments' => [
      'field' => 'Field name.',
    ],
    'options' => [
      'limit' => 'Limit on the number of deletions to do.',
    ],  
    'examples' => [
      'drush paragraphs-revisions-delete field_article_json' => 'Delete all unused paragraph revisions for the field field_article_json.',
      'drush paragraphs-revisions-delete' => 'Delete all unused paragraph revisions on any field.',  
    ],
  ];
  return $items;
}

/**
 * Callback function drush_jcms_notifications_article_import_all().
 */
function drush_jcms_migrate_paragraphs_revisions_delete($field = '') {
  $limit = drush_get_option('limit') ? (int) drush_get_option('limit') : 0;
  
  // Count number of paragraph revisions that are not a default revision
  $query = db_select('paragraphs_item_revision', 'pir');
  if (!empty($field) && db_table_exists('node__' . $field)) {
    $query->join('node__' . $field, 'nf', 'nf.' . $field . '_target_id=pir.id');
  }
  $query->leftjoin('paragraphs_item', 'pi', 'pi.revision_id=pir.revision_id');
  $query->isNull('pi.id');
  $count = $query->countQuery()->execute()->fetch()->expression;
  drush_print(dt('Number of revisions to be deleted is !count.', ['!count' => $count]));
  if ($count == 0 && !empty($field)) {
    db_query("OPTIMIZE TABLE node__" . $field);
    db_query("OPTIMIZE TABLE paragraphs_item_revision");
    db_query("OPTIMIZE TABLE paragraphs_item_revision_field_data");
    drush_print(dt('No revisions left to delete so database tables have been optimized.'));
  }
  
  if ($count > 0 && $limit >= 0) {
    $count_deleted = 0;
    do {
      $query = db_select('paragraphs_item_revision', 'pir');
      if (!empty($field)) {
        $query->join('node__' . $field, 'nf', 'nf.' . $field . '_target_id=pir.id');
      }
      $query->leftjoin('paragraphs_item', 'pi', 'pi.revision_id=pir.revision_id');
      $query->isNull('pi.id');
      $query->addField('pir', 'revision_id');
      if ($limit > 100) {
        $query->range(0, 100);
        $limit -= 100;
      }
      else if ($limit > 0) {
        $query->range(0, $limit);
        $limit = -1;
      }
      else {
        $query->range(0, 100);
      }
      $revision_ids = $query->execute()->fetchCol();
      if ($revision_ids) {
        foreach ($revision_ids as $revision_id) {
          try {
            \Drupal::entityTypeManager()->getStorage('paragraph')->deleteRevision($revision_id);
            $count_deleted++;
            if (($count_deleted % 100) == 0) {
              drush_print(dt('Number of revisions deleted is !count.', ['!count' => $count_deleted]));
            }
          }
          catch (\Exception $e) {
            // Cannot delete default revision exception thrown
          }
        }
      }
      else {
        // No more revisions to delete
        $limit = -1;
        break;
      }
    } while($limit >= 0);
    drush_print(dt('Total number of revisions deleted is !count.', ['!count' => $count_deleted]));
  }
}
