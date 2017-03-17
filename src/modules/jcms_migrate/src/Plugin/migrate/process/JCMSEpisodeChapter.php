<?php

namespace Drupal\jcms_migrate\Plugin\migrate\process;

use Drupal\jcms_article\Entity\ArticleVersions;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\node\Entity\Node;

/**
 * Process the episode chapter values into paragraphs.
 *
 * @MigrateProcessPlugin(
 *   id = "jcms_episode_chapter"
 * )
 */
class JCMSEpisodeChapter extends AbstractJCMSContainerFactoryPlugin {

  use JMCSCheckMarkupTrait;

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!empty($value)) {
      $values = [
        'type' => 'podcast_chapter',
        'title' => $this->checkMarkup($value['title']),
        'field_podcast_chapter_time' => [
          'value' => $value['time'],
        ],
      ];

      if (!empty($value['impact_statement'])) {
        $values['field_impact_statement'] = [
          'value' => $this->checkMarkup($value['impact_statement']),
          'format' => 'basic_html',
        ];
      }

      if (!empty($value['content'])) {
        $values['field_related_content'] = [];
        foreach ($value['content'] as $content) {
          switch ($content['type']) {
            case 'collection':
              $values['field_related_content'][] = ['target_id' => $this->migrationDestionationIDs('jcms_collections_db', $content['source'], $migrate_executable, $row, $destination_property)];
              break;
            case 'article':
              $crud_service = \Drupal::service('jcms_migrate.article_crud');
              if ($article_nid = $crud_service->getNodeIdByArticleId($content['source'])) {
                $values['field_related_content'][] = ['target_id' => $article_nid];
              }
              else {
                $article_versions = new ArticleVersions($content['source']);
                $article = $crud_service->createArticle($article_versions);
                $values['field_related_content'][] = ['target_id' => $article->id()];
              }
              break;
            default:
          }
        }
      }
      $node = Node::create($values);
      $node->save();
      return [
        'target_id' => $node->id(),
      ];
    }
    return NULL;
  }

}
