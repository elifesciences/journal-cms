<?php

namespace Drupal\jcms_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Process the aims and scope values into paragraphs.
 *
 * @MigrateProcessPlugin(
 *   id = "jcms_aims_and_scope"
 * )
 */
class JCMSAimsAndScope extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!empty($value)) {
      return $this->processItemValue($value);
    }

    return NULL;
  }

  /**
   * Process item value.
   */
  private function processItemValue(array $value) {
    if (!empty($value['text'])) {
      $value['text'] = $this->checkMarkup($value['text'], 'basic_html');
    }

    if ($value['type'] === 'paragraph') {
      $paragraph = Paragraph::create([
        'type' => 'paragraph',
        'field_block_html' => [
          'value' => $this->checkMarkup($value['text'], 'basic_html'),
          'format' => 'basic_html',
        ],
      ]);
      $paragraph->save();
      return [
        'target_id' => $paragraph->id(),
        'target_revision_id' => $paragraph->getRevisionId(),
      ];
    }

    return NULL;
  }

  /**
   * Check markup.
   */
  private function checkMarkup(string $html, string $format_id = 'basic_html') {
    return check_markup($html, $format_id);
  }

}
