<?php

namespace Drupal\jcms_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\paragraphs\Entity\Paragraph;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;

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
   * @var \Drupal\migrate\Row
   */
  protected $row;

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $this->row = $row;
    if (!empty($value)) {
      if (!isset($this->configuration['multiple']) || $this->configuration['multiple'] === FALSE) {
        return $this->processItemValue($value);
      }
      else {
        $items = [];
        foreach ($value as $val) {
          if ($item = $this->processItemValue($val)) {
            $items[] = $this->processItemValue($val);
          }
        }
        return $items;
      }
    }

    return NULL;
  }

  /**
   * @return \Drupal\migrate\Row
   */
  private function getRow() {
    return $this->row;
  }

  private function imagePath($type = NULL, $time = NULL) {
    $destination = $this->getRow()->getDestination();
    if (!$type) {
      $type = (!empty($destination['vid'])) ? $destination['vid'] : $destination['type'];
    }
    if (!$time) {
      $time = (!empty($destination['created'])) ? $destination['created'] : time();
    }
    $folder = $type . '/' . date('Y-m', $time) . '/';
    return 'public://iiif/' . $folder;
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

        $image_path = $this->imagePath('content');
        if ($source && file_exists($source)) {
          file_prepare_directory($image_path, FILE_CREATE_DIRECTORY);
          $new_filename = JCMSImage::transliteration(basename($source));
          $uri = file_unmanaged_copy($source, $image_path . $new_filename, FILE_EXISTS_REPLACE);
          $file = \Drupal::entityTypeManager()->getStorage('file')->create(['uri' => $uri]);
          $file->save();
        }
        elseif (preg_match('/^http/', $image) && $data = $this->getFile($image)) {
          $new_filename = JCMSImage::transliteration(basename($image));
          file_prepare_directory($image_path, FILE_CREATE_DIRECTORY);
          $file = file_save_data($data, $image_path . $new_filename, FILE_EXISTS_REPLACE);
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
        else {
          $values = NULL;
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
      case 'question':
        $values['field_block_title'] = [
          'value' => $value['question'],
        ];
        $content = [];
        foreach ($value['answer'] as $item) {
          $content[] = $this->processItemValue($item);
        }
        $values['field_block_question_answer'] = $content;
        break;
      case 'code':
        $values['field_block_code'] = [
          'value' => $value['code'],
        ];
        break;
    }
    if (!empty($values)) {
      $paragraph = Paragraph::create($values);
      $paragraph->save();
      return [
        'target_id' => $paragraph->id(),
        'target_revision_id' => $paragraph->getRevisionId(),
      ];
    }
  }

  function getFile($filename) {
    $guzzle = new Client();
    try {
      $response = $guzzle->get($filename, ['timeout' => 5, 'http_errors' => FALSE]);
      if ($response->getStatusCode() == 200) {
        return $response->getBody()->getContents();
      }

      error_log(sprintf("File %s didn't download. (return code %d)", $filename, $response->getStatusCode()));
      return FALSE;
    }
    catch (ConnectException $e) {
      error_log(sprintf("File %s didn't download. (%s)", $filename, $e->getMessage()));
    }
  }

}
