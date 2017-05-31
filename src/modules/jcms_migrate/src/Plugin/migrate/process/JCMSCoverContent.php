<?php

namespace Drupal\jcms_migrate\Plugin\migrate\process;

use Drupal\jcms_article\Entity\ArticleVersions;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Process the cover content value into an entity reference value.
 *
 * @MigrateProcessPlugin(
 *   id = "jcms_cover_content"
 * )
 */
class JCMSCoverContent extends AbstractJCMSContainerFactoryPlugin {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!empty($value)) {
      if (!isset($this->configuration['multiple']) || $this->configuration['multiple'] === FALSE) {
        if (is_string($value)) {
          if (strpos($value, '{') === FALSE) {
            $value = '{' . $value . '}';
          }
          $value = json_decode($value, TRUE);
        }
        $item = $this->processItemValue($value['type'], $value['source'], $migrate_executable, $row, $destination_property);
        return $item;
      }
      else {
        $items = [];
        foreach ($value as $val) {
          $items[] = $this->processItemValue($val['type'], $val['source'], $migrate_executable, $row, $destination_property);
        }
        return $items;
      }
    }

    return NULL;
  }

  private function processItemValue($type, $source, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    switch ($type) {
      case 'collection':
        if (is_numeric($source)) {
          return $this->migrationDestionationIDs('jcms_collections_db', $source, $migrate_executable, $row, $destination_property);
        }
        else {
          return $this->migrationDestionationIDs('jcms_collections_json', $source, $migrate_executable, $row, $destination_property);
        }
        break;
      case 'article':
        $crud_service = \Drupal::service('jcms_migrate.article_crud');
        if ($nid = $crud_service->getNodeIdByArticleId($source)) {
          return $nid;
        }
        else {
          $article_versions = new ArticleVersions($source);
          $node = $crud_service->createArticle($article_versions);
          return $node->id();
        }
        break;
    }

    return NULL;
  }

}
