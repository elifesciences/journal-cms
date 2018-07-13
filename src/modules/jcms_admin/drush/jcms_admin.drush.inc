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
    'json-html-migrate' => [
      'description' => 'Migrate content from content blocks to single ckeditor.',
      'drupal dependencies' => ['jcms_admin'],
      'arguments' => [
        'type' => 'Specify which content type to migrate (e.g. blog_article)',
      ],
      'options' => [
        'limit' => 'Limit on the number of migrations to do.',
        'skip-completed' => 'Skip completed (default: false)',
      ],
      'examples' => [
        'drush json-html-migrate blog_article' => 'Migrate all blog_article content blocks to single ckeditor field.',
      ],
    ],
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
  _jcms_admin_transfer_content($node, $to_live);
  drush_print(dt('Transfer complete!'));
}

/**
 * Callback function drush_jcms_migrate_json_html_migrate().
 */
function drush_jcms_migrate_json_html_migrate(string $type) {
  $limit = drush_get_option('limit') ? (int) drush_get_option('limit') : 0;
  $skip_completed = drush_get_option('skip-completed') ? TRUE : FALSE;
  $denormalizer = \Drupal::service('jcms_admin.json_html_denormalizer');
  $new_fids = [];
  $preview_fids = [];
  $folders = [
    'blog_article' => 'blog-article',
    'labs_experiment' => 'labs-post',
    'press_package' => 'for-the-press',
  ];

  $query = \Drupal::entityQuery('node')
    ->exists('field_content_processed_json')
    ->condition('type', $type);

  if ($skip_completed) {
    $query->notExists('field_content_html');
  }

  if ($limit > 0) {
    $query->range(0, $limit);
  }

  $result = $query->execute();

  if ($result) {
    $total = count($result);
    $nodes = Node::loadMultiple($result);
    $num = 0;
    foreach ($nodes as $node) {
      $data = [
        'content' => json_decode($node->get('field_content_processed_json')->getString(), TRUE),
      ];
      $images = $denormalizer->gatherImages($data['content']);

      if ($node->bundle() === 'interview') {
        if ($node->get('field_interview_cv')->count()) {
          $data['interviewee'] = [
            'name' => [
              'preferred' => $node->get('field_person_preferred_name')->getString(),
            ],
            'cv' => [],
          ];
          foreach ($node->get('field_interview_cv') as $paragraph) {
            $cv_item = $paragraph->get('entity')->getTarget()->getValue();
            $data['interviewee']['cv'][] = [
              'date' => $cv_item->get('field_cv_item_date')->getString(),
              'text' => _drush_jcms_migrate_field_value_formatted($cv_item->get('field_block_html')),
            ];
          }
        }
      }

      if (!empty($images)) {
        $new_values = [];
        $preview_values = [];
        foreach ($images as $image) {
          $result = \Drupal::entityQuery('file')
            ->condition('uri', $image)
            ->execute();
          if ($result) {
            $result = array_values($result);
            if ($file = File::load($result[0])) {
              $folder = $folders[$node->bundle()] ?? $node->bundle();
              $new_file_uri = preg_replace('/\/iiif\/content\//', '/iiif/' . $folder . '-content/', $image);
              file_prepare_directory(\Drupal::service('file_system')->dirname($new_file_uri), FILE_CREATE_DIRECTORY);
              $new_file = file_copy($file, $new_file_uri, FILE_EXISTS_REPLACE);
              $new_file->save();
              $new_fid = $new_file->id();
              $new_uuid = $new_file->uuid();
              $new_src = file_url_transform_relative(file_create_url($new_file->getFileUri()));
              $new_fids[$image] = [
                'fid' => $new_fid,
                'uuid' => $new_uuid,
                'src' => $new_src,
              ];
              $new_values[] = [
                'target_id' => $new_fid,
              ];

              $preview_file_uri = preg_replace('/\/iiif\/content\//', '/iiif/' . $folder . '-preview/', $image);
              file_prepare_directory(\Drupal::service('file_system')->dirname($preview_file_uri), FILE_CREATE_DIRECTORY);
              $preview_file = file_copy($file, $preview_file_uri, FILE_EXISTS_REPLACE);
              $preview_file->save();
              $preview_fid = $preview_file->id();
              $preview_uuid = $preview_file->uuid();
              $preview_src = file_url_transform_relative(file_create_url($preview_file->getFileUri()));
              $preview_fids[$image] = [
                'fid' => $preview_fid,
                'uuid' => $preview_uuid,
                'src' => $preview_src,
              ];
              $preview_values[] = [
                'target_id' => $preview_fid,
              ];
            }
          }
        }
        if (!empty($new_values)) {
          $node->set('field_content_images', $new_values);
          $node->set('field_content_images_preview', $preview_values);
        }
      }

      $new_html = $denormalizer->denormalize($data, Model::class, NULL, ['fids' => $new_fids]);
      $node->set('field_content_html', [
        'value' => $new_html,
        'format' => 'full_html',
      ]);

      $preview_html = $denormalizer->denormalize($data, Model::class, NULL, ['fids' => $preview_fids]);
      $node->set('field_content_html_preview', [
        'value' => $preview_html,
        'format' => 'full_html',
      ]);

      drush_print(dt('Processed :num of :total (:nid).', [
        ':num' => ++$num,
        ':total' => $total,
        ':nid' => $node->id(),
      ]));
      $node->save();
    }
  }

  drush_print(dt('Finished!'));
}

/**
 * Get formatted field value.
 */
function _drush_jcms_migrate_field_value_formatted(FieldItemListInterface $data) {
  $view = $data->view();
  unset($view['#theme']);
  $output = \Drupal::service('renderer')->renderPlain($view);
  $output = preg_replace('/(<img [^>]*src=\")(\/[^\"]+)/', '$1' . \Drupal::request()->getSchemeAndHttpHost() . '$2', $output);
  $output = str_replace(chr(194) . chr(160), ' ', $output);
  $output = preg_replace('/\n/', '', $output);
  return trim($output);
}
