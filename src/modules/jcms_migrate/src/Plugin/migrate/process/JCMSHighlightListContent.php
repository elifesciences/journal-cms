<?php

namespace Drupal\jcms_migrate\Plugin\migrate\process;

use Drupal\jcms_article\Entity\ArticleVersions;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\node\Entity\Node;

/**
 * Process the highlight list content values into entity reference values.
 *
 * @MigrateProcessPlugin(
 *   id = "jcms_highlight_list_content"
 * )
 */
class JCMSHighlightListContent extends AbstractJCMSContainerFactoryPlugin {

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
        return $this->generateHighlightItem($value['title'], $value['type'], $value['source'], $migrate_executable, $row, $destination_property);
      }
      else {
        $items = [];
        foreach ($value as $val) {
          $items[] = $this->generateHighlightItem($val['title'], $val['type'], $val['source'], $migrate_executable, $row, $destination_property);
        }
        return $items;
      }
    }

    return NULL;
  }

  private function generateHighlightItem($title, $type, $source, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if ($type == 'random') {
      $types = [
        'blog_article',
        'collection',
        'interview',
        'labs_experiement',
        'podcast_episode',
        'podcast_chapter',
        'article',
      ];
      $source = 'random';
    }
    else {
      $types = [$type];
    }
    if ($source == 'random') {
      $query = \Drupal::entityQuery('node')
        ->condition('status', NODE_PUBLISHED)
        ->condition('changed', REQUEST_TIME, '<')
        ->condition('type', $types, 'IN')
        ->range(0, 1);

      if (!empty($this->exclude())) {
        $query->condition('nid', $this->exclude(), 'NOT IN');
      }
      $query->addTag('random');
      $nids = $query->execute();
      if ($nids) {
        $nid = reset($nids);
      }
      else {
        return FALSE;
      }
    }
    else {
      $nid = $this->processItemValue($type, $source, $migrate_executable, $row, $destination_property);
    }

    if ($title == 'inherit') {
      /* @var \Drupal\node\Entity\Node $node */
      $node = \Drupal\node\Entity\Node::load($nid);
      if ($node->bundle() == 'article') {
        $crud_service = \Drupal::service('jcms_article.article_crud');
        $article = $crud_service->getArticle($node);
        $title = $article->title;
      }
      else {
        $title = $node->label();
      }
    }
    $item = Node::create([
      'type' => 'highlight_item',
      'title' => $title,
      'uid' => 1,
      'status' => NODE_PUBLISHED,
    ]);
    $item->field_highlight_item = [
      [
        'target_id' => $nid,
      ],
    ];
    $item->save();
    $this->exclude($nid);

    return $item->id();
  }

  private function processItemValue($type, $source, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    switch ($type) {
      case 'blog_article':
        return $this->migrationDestionationIDs('jcms_blog_articles_db', $source, $migrate_executable, $row, $destination_property);
        break;
      case 'collection':
        if (is_int($source)) {
          return $this->migrationDestionationIDs('jcms_collections_db', $source, $migrate_executable, $row, $destination_property);
        }
        else {
          return $this->migrationDestionationIDs('jcms_collections_json', $source, $migrate_executable, $row, $destination_property);
        }
        break;
      case 'interview':
        return $this->migrationDestionationIDs('jcms_interviews_db', $source, $migrate_executable, $row, $destination_property);
        break;
      case 'podcast_episode':
        return $this->migrationDestionationIDs('jcms_podcast_episodes_json', $source, $migrate_executable, $row, $destination_property);
        break;
      case 'podcast_chapter':
        list($source, $delta) = explode(':', $source);
        $episode_nid = $this->migrationDestionationIDs('jcms_podcast_episodes_json', $source, $migrate_executable, $row, $destination_property);
        $episode = Node::load($episode_nid);
        $chapter = $episode->get('field_episode_chapter')->get($delta - 1)->getValue();
        return $chapter['target_id'];
        break;
      case 'article':
        $crud_service = \Drupal::service('jcms_article.article_crud');
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

  /**
   * Create exclusion list.
   *
   * @param int|null $nid
   * @return array
   */
  private function exclude($nid = NULL) {
    static $exclusions = [];
    if (!empty($nid) && !in_array($nid, $exclusions)) {
      $exclusions[] = $nid;
    }

    return $exclusions;
  }

}
