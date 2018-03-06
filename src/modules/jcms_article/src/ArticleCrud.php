<?php

namespace Drupal\jcms_article;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\jcms_article\Entity\ArticleVersions;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Class ArticleCrud.
 *
 * @package Drupal\jcms_article
 * @todo Look to share more code with \Drupal\jcms_article\Hooks\NodePreSave.
 */
class ArticleCrud {

  /**
   * EntityTypeManager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Flag set to TRUE to skip updating articles.
   *
   * @var bool
   */
  protected $skipUpdates = FALSE;

  /**
   * ArticleCrud constructor.
   */
  public function __construct(EntityTypeManager $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Set the flag to skip updating articles.
   */
  public function skipUpdates() {
    $this->skipUpdates = TRUE;
  }

  /**
   * Helper method to decide whether to created, update or delete a node.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   Return entity, if found.
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
      elseif (!$this->skipUpdates) {
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
   */
  public function createArticle(ArticleVersions $articleVersions) : EntityInterface {
    $node = Node::create([
      'type' => 'article',
      'title' => $articleVersions->getId(),
    ]);
    $paragraph = $this->createParagraph($node, $articleVersions);
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
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   Return updated entity, if found.
   */
  public function updateArticle(ArticleVersions $articleVersions) {
    $nid = $this->getNodeIdByArticleId($articleVersions->getId());
    if (!$nid) {
      return NULL;
    }
    $node = Node::load($nid);
    if ($node->get('field_article_json')->getValue()) {
      $paragraph = $this->updateParagraph($node, $articleVersions);
    }
    else {
      $paragraph = $this->createParagraph($node, $articleVersions);
    }
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
   * Updates a paragraph.
   */
  public function updateParagraph(EntityInterface $node, ArticleVersions $articleVersions) : EntityInterface {
    $pid = $node->get('field_article_json')->getValue()[0]['target_id'];
    $paragraph = Paragraph::load($pid);
    $published = $articleVersions->getLatestPublishedVersionJson();
    // Store the published JSON if no unpublished exists.
    $unpublished = $articleVersions->getLatestUnpublishedVersionJson() ?: $published;
    $paragraph->set('field_article_unpublished_json', $unpublished);
    $paragraph->set('field_article_published_json', $published);
    $paragraph->setNewRevision();
    $paragraph->save();
    return $paragraph;
  }

  /**
   * Creates a new paragraph.
   */
  public function createParagraph(EntityInterface $node, ArticleVersions $articleVersions) : EntityInterface {
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
    return $paragraph;
  }

  /**
   * Deletes an article node.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   Return entities, if found.
   */
  public function deleteArticle(ArticleVersions $articleVersions) {
    $node_id = $this->getNodeIdByArticleId($articleVersions->getId());
    $node = $this->entityTypeManager->getStorage('node')->load($node_id);
    if (!$node) {
      return NULL;
    }
    return $this->entityTypeManager->getStorage('node')->delete([$node]);
  }

  /**
   * Checks if node with the article ID already exists and returns the node ID.
   */
  public function getNodeIdByArticleId(string $articleId) : int {
    $query = \Drupal::entityQuery('node')->condition('title', $articleId);
    $result = $query->execute();
    return !empty($result) ? reset($result) : 0;
  }

  /**
   * Get article snippet from node.
   *
   * @return mixed|bool
   *   Return article snippet, if found.
   */
  public function getArticle(EntityInterface $node, $preview = FALSE) {
    $pid = $node->get('field_article_json')->getValue()[0]['target_id'];
    $paragraph = Paragraph::load($pid);
    if ($preview) {
      return json_decode($paragraph->get('field_article_unpublished_json')->getString());
    }
    else {
      if ($paragraph->get('field_article_published_json')->getValue()) {
        return json_decode($paragraph->get('field_article_published_json')->getString());
      }
      else {
        return FALSE;
      }
    }
  }

}
