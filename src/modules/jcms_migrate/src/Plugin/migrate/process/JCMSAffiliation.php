<?php

namespace Drupal\jcms_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Process the affiliation values into paragraphs.
 *
 * @MigrateProcessPlugin(
 *   id = "jcms_affiliation"
 * )
 */
class JCMSAffiliation extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    list($name, $country) = $value;
    $country = (!empty($country)) ? $country : 'GB';

    $paragraphs = [];
    if (!empty($name)) {
      $values = [
        'type' => 'affiliation',
        'field_block_title_multiline' => ['value' => $name],
        'field_block_country' => ['value' => $country],
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

}
