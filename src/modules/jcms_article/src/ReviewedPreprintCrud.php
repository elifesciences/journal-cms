<?php

namespace Drupal\jcms_article;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\jcms_article\Entity\ReviewedPreprint;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Handle changes to ReviewedPreprint snippets.
 *
 * @package Drupal\jcms_article
 * @todo Look to share more code with \Drupal\jcms_article\Hooks\NodePreSave.
 */
class ReviewedPreprintCrud {

  /**
   * EntityTypeManager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Flag set to TRUE to skip updating reviewed preprints.
   *
   * @var bool
   */
  protected $skipUpdates = FALSE;

  /**
   * ReviewedPreprintCrud constructor.
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
  public function crudReviewedPreprint(ReviewedPreprint $reviewedPreprint) {
    $node = NULL;
    $nid = $this->getNodeIdByArticleId($reviewedPreprint->getId());
    $action = $reviewedPreprint->getAction();
    // If we have a node with the requested article ID.
    if ($nid) {
      // Delete it.
      if ($action == ReviewedPreprint::DELETE) {
        $node = $this->deleteArticle($reviewedPreprint);
      }
      // Update it.
      elseif (!$this->skipUpdates) {
        $node = $this->updateArticle($reviewedPreprint);
      }
    }
    // Create a new node if we have no node ID with the reviewed preprint ID requested.
    else {
      $node = $this->createArticle($reviewedPreprint);
    }
    return $node;
  }

  /**
   * Creates a new article node.
   */
  public function createArticle(ReviewedPreprint $reviewedPreprint) : EntityInterface {
    $node = Node::create([
      'type' => 'article',
      'title' => $reviewedPreprint->getId(),
    ]);
    $paragraph = $this->createParagraph($reviewedPreprint);
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
  public function updateArticle(ReviewedPreprint $reviewedPreprint) {
    $nid = $this->getNodeIdByArticleId($reviewedPreprint->getId());
    if (!$nid) {
      return NULL;
    }
    $node = Node::load($nid);
    if ($node->get('field_article_json')->getValue()) {
      $paragraph = $this->updateParagraph($node, $reviewedPreprint);
    }
    else {
      $paragraph = $this->createParagraph($reviewedPreprint);
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
  public function updateParagraph(EntityInterface $node, ReviewedPreprint $reviewedPreprint) : EntityInterface {
    $pid = $node->get('field_article_json')->getValue()[0]['target_id'];
    $paragraph = Paragraph::load($pid);
    $published = $reviewedPreprint->getJson();
    $paragraph->set('field_reviewed_preprint_json', $published);
    $paragraph->setNewRevision();
    $paragraph->save();
    return $paragraph;
  }

  /**
   * Creates a new paragraph.
   */
  public function createParagraph(ReviewedPreprint $reviewedPreprint) : EntityInterface {
    $published = $reviewedPreprint->getJson();
    $config = [
      'type' => 'json',
      'field_reviewed_preprint_json' => [
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
  public function deleteArticle(ReviewedPreprint $reviewedPreprint) {
    $node_id = $this->getNodeIdByArticleId($reviewedPreprint->getId());
    $node = $this->entityTypeManager->getStorage('node')->load($node_id);
    if (!$node) {
      return NULL;
    }
    return $this->entityTypeManager->getStorage('node')->delete([$node]);
  }

  /**
   * Checks if node with the reviewed preprint ID already exists and returns the node ID.
   */
  public function getNodeIdByArticleId(string $reviewedPreprintId) : int {
    $query = \Drupal::entityQuery('node')->condition('title', $reviewedPreprintId);
    $result = $query->execute();
    return !empty($result) ? reset($result) : 0;
  }

}
