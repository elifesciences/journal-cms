<?php

namespace Drupal\jcms_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process the content value by applying a HTML filter.
 *
 * @MigrateProcessPlugin(
 *   id = "jcms_filter_html"
 * )
 */
class JCMSFilterHtml extends ProcessPluginBase {

  use JMCSCheckMarkupTrait;

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!empty($value)) {
      $format_id = isset($this->configuration['format_id']) ? $this->configuration['format_id'] : 'basic_html';
      $formatted_value = $this->checkMarkup($value, $format_id);
      if (!empty($formatted_value)) {
        return $formatted_value;
      }
    }

    return NULL;
  }

}
