<?php

namespace Drupal\jcms_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Process the media contact values into paragraphs.
 *
 * @MigrateProcessPlugin(
 *   id = "jcms_media_contact"
 * )
 */
class JCMSMediaContact extends ProcessPluginBase {

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

  private function processItemValue($value) {
    $values = [
      'type' => 'media_contact',
      'field_block_preferred_name' => [
        'value' => $value['name']['preferred'],
      ],
      'field_block_index_name' => [
        'value' => $value['name']['index'],
      ],
    ];

    if (!empty($value['emailAddresses'])) {
      $values['field_block_email'] = [];
      foreach ($value['emailAddresses'] as $email) {
        $values['field_block_email'][] = [
          'value' => $email,
        ];
      }
    }

    if (!empty($value['phoneNumbers'])) {
      $values['field_block_phone_number'] = [];
      foreach ($value['phoneNumbers'] as $phone_number) {
        $values['field_block_phone_number'][] = [
          'value' => $phone_number,
        ];
      }
    }

    if (!empty($value['affiliations'])) {
      $values['field_block_affiliation'] = [];
      foreach ($value['affiliations'] as $affiliation) {
        $affiliation_values = [
          'type' => 'venue',
          'field_block_title_multiline' => [
            'value' => implode("\n", $affiliation['name']),
          ],
        ];

        $paragraph = Paragraph::create($affiliation_values);
        $paragraph->save();

        $values['field_block_affiliation'][] = [
          'target_id' => $paragraph->id(),
          'target_revision_id' => $paragraph->getRevisionId(),
        ];
      }
    }

    $paragraph = Paragraph::create($values);
    $paragraph->save();
    return [
      'target_id' => $paragraph->id(),
      'target_revision_id' => $paragraph->getRevisionId(),
    ];
  }

}
