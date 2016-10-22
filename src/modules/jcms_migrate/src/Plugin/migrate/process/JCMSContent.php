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

  use JMCSCheckMarkupTrait;

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!empty($value)) {
      if (!isset($this->configuration['multiple']) || $this->configuration['multiple'] === FALSE) {
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

    if (!empty($value['text'])) {
      $value['text'] = $this->checkMarkup($value['text'], 'basic_html');
    }
    switch ($value['type']) {
      case 'paragraph':
        $values['field_block_html'] = [
          'value' => $this->checkMarkup($value['text'], 'basic_html'),
          'format' => 'basic_html',
        ];
        break;
      case 'blockquote':
        $values['field_block_html'] = [
          'value' => $this->checkMarkup($value['text'], 'basic_html'),
          'format' => 'basic_html',
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
          $values['field_block_html'] = [
            'value' => $this->checkMarkup($value['text'], 'basic_html'),
            'format' => 'basic_html',
          ];
        }
        $image = $value['image'];

        if (preg_match('/^http/', $image) && preg_match('/elifesciences\.org\/sites\/default\/files\/(?P<file>.*)/', $image, $match)) {
          $source = DRUPAL_ROOT . '/../scripts/legacy_cms_files/' . $match['file'];
        }
        elseif (!preg_match('/^http/', $image)) {
          $source = drupal_get_path('module', 'jcms_migrate') . '/migration_assets/images/' . $image;
        }
        else {
          $source = NULL;
        }

        if ($source && file_exists($source)) {
          $uri = file_unmanaged_copy($source, NULL, FILE_EXISTS_REPLACE);
          $file = \Drupal::entityTypeManager()->getStorage('file')->create(['uri' => $uri]);
          $file->save();
        }
        elseif (preg_match('/^http/', $image) && $data = $this->getFile($image)) {
          $file = file_save_data($data, 'public://' . basename($image), FILE_EXISTS_REPLACE);
        }
        else {
          $file = NULL;
        }
        if (!empty($file)) {
          $values['field_block_image'] = [
            'target_id' => $file->id(),
          ];
          if (!empty($value['alt'])) {
            $values['field_block_image']['alt'] = $value['alt'];
          }
        }
        break;
      case 'table':
        $values['field_block_html'] = [
          'value' => $this->checkMarkup($value['html'], 'basic_html'),
          'format' => 'basic_html',
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
        $values['field_block_html'] = [
          'value' => $this->checkMarkup($value['text'], 'basic_html'),
          'format' => 'basic_html',
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

  function getFile($filename) {
    $headers = get_headers($filename);
    $status = (int) substr($headers[0], 9, 3);
    if ($status === 200) {
      return file_get_contents($filename);
    }

    return FALSE;
  }

}
