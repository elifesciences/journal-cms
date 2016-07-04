<?php

namespace Drupal\labs_experiment\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Process the content values into paragraphs.
 *
 * @MigrateProcessPlugin(
 *   id = "labs_content"
 * )
 */
class LabsContent extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!empty($value)) {
      return $this->processItemValue($value);
    }

    return NULL;
  }

  private function processItemValue($value) {
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
        $values['field_block_text'] = [
          'value' => $value['text'],
        ];
        $image = $value['image'];
        $image_alt = $value['image_alt'];
        $source = drupal_get_path('module', 'labs_experiment') . '/migration_assets/images/' . $image;
        if ($uri = file_unmanaged_copy($source)) {
          $file = \Drupal::entityTypeManager()->getStorage('file')->create(['uri' => $uri]);
          $file->save();
          $values['field_block_image'] = [
            'target_id' => $file->id(),
            'alt' => $image_alt,
          ];
        }
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
    }
    $paragraph = Paragraph::create($values);
    $paragraph->save();
    return [
      'target_id' => $paragraph->id(),
      'target_revision_id' => $paragraph->getRevisionId(),
    ];
  }

}