<?php

namespace Drupal\jcms_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Process the content values into paragraphs.
 *
 * @MigrateProcessPlugin(
 *   id = "jcms_content"
 * )
 */
class JCMSContent extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!empty($value)) {
      if (!isset($this->configuration['multiple'])) {
        return $this->processItemValue($value);
      }
      else {
        $items = [];
        foreach ($value as $val) {
          $items[] = $this->processItemValue($val);
        }
        return $items;
      }
    }

    return NULL;
  }

  private function processItemValue($value, $type = NULL) {
    if ($type == 'list_item' && is_string($value)) {
      $value = [
        'type' => $type,
        'text' => $value,
      ];
    }
    $values = [
      'type' => $value['type'],
    ];
    switch ($value['type']) {
      case 'paragraph':
        $values['field_block_text'] = [
          'value' => $value['text'],
        ];
        break;
      case 'blockquote':
        $values['field_block_text'] = [
          'value' => $value['text'],
        ];
        $values['field_block_citation'] = [
          'value' => $value['citation'],
        ];
        break;
      case 'youtube':
        $values['field_block_youtube_id'] = [
          'value' => $value['id'],
        ];
        $values['field_block_youtube_height'] = [
          'value' => $value['height'],
        ];
        $values['field_block_youtube_width'] = [
          'value' => $value['width'],
        ];
        break;
      case 'image':
        if (!empty($value['text'])) {
          $values['field_block_text'] = [
            'value' => $value['text'],
          ];
        }
        $image = $value['image'];
        $alt = $value['alt'];
        $source = drupal_get_path('module', 'jcms_migrate') . '/migration_assets/images/' . $image;
        if ($uri = file_unmanaged_copy($source)) {
          $file = \Drupal::entityTypeManager()->getStorage('file')->create(['uri' => $uri]);
          $file->save();
          $values['field_block_image'] = [
            'target_id' => $file->id(),
            'alt' => $alt,
          ];
        }
        break;
      case 'table':
        $values['field_block_html'] = [
          'value' => $value['html'],
          'format' => 'full_html',
        ];
        break;
      case 'section':
        $values['field_block_title'] = [
          'value' => $value['title'],
        ];
        $content = [];
        foreach ($value['content'] as $item) {
          $content[] = $this->processItemValue($item);
        }
        $values['field_block_content'] = $content;
        break;
      case 'list':
        $values['field_block_list_ordered'] = [
          'value' => $value['ordered'] ? 1 : 0,
        ];
        $content = [];
        foreach ($value['items'] as $item) {
          $content[] = $this->processItemValue($item, 'list_item');
        }
        $values['field_block_list_items'] = $content;
        break;
      case 'list_item':
        $values['field_block_text'] = [
          'value' => $value['text'],
        ];
        break;
    }
    $paragraph = Paragraph::create($values);
    $paragraph->save();
    return [
      'target_id' => $paragraph->id(),
      'target_revision_id' => $paragraph->getRevisionId(),
    ];
  }

}