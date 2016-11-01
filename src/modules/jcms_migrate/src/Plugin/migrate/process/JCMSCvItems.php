<?php

namespace Drupal\jcms_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Process the cv item values into paragraphs.
 *
 * @MigrateProcessPlugin(
 *   id = "jcms_cv_items"
 * )
 */
class JCMSCvItems extends ProcessPluginBase {

  use JMCSCheckMarkupTrait;

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    list($cv_dates, $cv_texts) = $value;

    $cv_items = $this->prepareCvItems($cv_dates, $cv_texts);

    if (!empty($cv_items)) {
      $paragraphs = [];
      foreach ($cv_items as $cv_item) {
        $values = [
          'type' => 'cv_item',
          'field_cv_item_date' => [
            'value' => $cv_item['date'],
          ],
          'field_block_html' => [
            'value' => $this->checkMarkup($cv_item['text'], 'basic_html'),
            'format' => 'basic_html',
          ],
        ];
        $paragraph = Paragraph::create($values);
        $paragraph->save();
        $paragraphs[] = [
          'target_id' => $paragraph->id(),
          'target_revision_id' => $paragraph->getRevisionId(),
        ];
      }

      return $paragraphs;
    }

    return NULL;
  }

  public function prepareCvItems($cv_dates, $cv_texts) {
    $delimiter = isset($this->configuration['delimiter']) ? $this->configuration['delimiter'] : '';

    $cv_dates = explode($delimiter, $cv_dates);
    $cv_texts = explode($delimiter, $cv_texts);

    $limit = min(count($cv_dates), count($cv_texts));

    $cv_items = [];
    if (!empty($limit)) {
      for ($i = 0; $i < $limit; $i++) {
        $cv_items[] = [
          'date' => trim($cv_dates[$i]),
          'text' => trim($cv_texts[$i]),
        ];
      }
    }

    return $cv_items;
  }

}
