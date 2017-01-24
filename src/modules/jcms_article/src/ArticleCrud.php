<?php

namespace Drupal\jcms_article;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\jcms_article\Entity\ArticleVersions;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Class ArticleCrud
 *
 * @package Drupal\jcms_article
 * @todo Look to share more code with \Drupal\jcms_article\Hooks\NodePreSave.
 */
class ArticleCrud {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * ArticleCrud constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   */
  public function __construct(EntityTypeManager $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Helper method to decide whether to created, update or delete a node.
   *
   * @param \Drupal\jcms_article\Entity\ArticleVersions $articleVersions
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   */
  public function crudArticle(ArticleVersions $articleVersions) {
    $node = NULL;
    $nid = $this->getNodeIdByArticleId($articleVersions->getId());
    $action = $articleVersions->getAction();
    // If we have a node with the requested article ID.
    if ($nid) {
      // Delete it.
      if ($action == ArticleVersions::DELETE) {
        $node = $this->deleteArticle($articleVersions);
      }
      // Update it.
      else {
        $node = $this->updateArticle($articleVersions);
      }
    }
    // Create a new node if we have no node ID with the article ID requested.
    else {
      $node = $this->createArticle($articleVersions);
    }
    return $node;
  }

  /**
   * Creates a new article node.
   *
   * @param \Drupal\jcms_article\Entity\ArticleVersions $articleVersions
   *
   * @return \Drupal\Core\Entity\EntityInterface
   */
  public function createArticle(ArticleVersions $articleVersions) {
    $node = Node::create([
      'type' => 'article',
      'title' => $articleVersions->getId(),
    ]);
    $published = $articleVersions->getLatestPublishedVersionJson();
    // Store the published JSON if no unpublished exists.
    $unpublished = $articleVersions->getLatestUnpublishedVersionJson() ?: $published;
    $config = [
      'type' => 'json',
      'field_article_unpublished_json' => [
        'value' => $unpublished,
      ],
      'field_article_published_json' => [
        'value' => $published,
      ],
    ];
    $paragraph = Paragraph::create($config);
    $paragraph->save();
    $node->field_article_json = [
      [
        'target_id' => $paragraph->id(),
        'target_revision_id' => $paragraph->getRevisionId(),
      ],
    ];
    $node->save();
    return $node;
  }

  /**
   * Updates an existing article node.
   *
   * @param \Drupal\jcms_article\Entity\ArticleVersions $articleVersions
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   */
  public function updateArticle(ArticleVersions $articleVersions) {
    $nid = $this->getNodeIdByArticleId($articleVersions->getId());
    if (!$nid) {
      return NULL;
    }
    $node = Node::load($nid);
    $pid = $node->get('field_article_json')->getValue()[0]['target_id'];
    $paragraph = Paragraph::load($pid);
    $published = $articleVersions->getLatestPublishedVersionJson();
    // Store the published JSON if no unpublished exists.
    $unpublished = $articleVersions->getLatestUnpublishedVersionJson() ?: $published;
    $paragraph->set('field_article_unpublished_json', $unpublished);
    $paragraph->set('field_article_published_json', $published);
    $paragraph->setNewRevision();
    $paragraph->save();
    $node->field_article_json = [
      [
        'target_id' => $paragraph->id(),
        'target_revision_id' => $paragraph->getRevisionId(),
      ],
    ];
    $node->save();
    return $node;
  }

  /**
   * Deletes an article node.
   *
   * @param \Drupal\jcms_article\Entity\ArticleVersions $articleVersions
   *
   * @return mixed
   */
  public function deleteArticle(ArticleVersions $articleVersions) {
    $node_id = $this->getNodeIdByArticleId($articleVersions->getId());
    return $this->entityTypeManager->getStorage('node')->delete([$node_id]);
  }

  /**
   * Checks if a node with the article ID already exists and returns the node ID.
   *
   * @param string $articleId
   *
   * @return int
   *   The node ID.
   */
  public function getNodeIdByArticleId(string $articleId) {
    $query = \Drupal::entityQuery('node')->condition('title', $articleId);
    $result = $query->execute();
    return !empty($result) ? reset($result) : 0;
  }

}
