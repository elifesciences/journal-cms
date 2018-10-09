<?php

namespace Drupal\jcms_digest;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\jcms_article\Entity\ArticleVersions;
use Drupal\jcms_digest\Entity\Digest;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Handle changes to Digest snippets.
 *
 * @package Drupal\jcms_digest
 */
class DigestCrud {

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
  public function crudDigest(Digest $digest) {
    if ($digest->getAction() === Digest::DELETE) {
      $node = $this->deleteDigest($digest);
    }
    else {
      $node = $this->upsertDigest($digest);
    }
    return $node;
  }

  /**
   * Upsert digest node.
   *
   * @return \Drupal\Core\Entity\EntityInterface|NULL
   *   Return updated entity, if found.
   * @throws \Exception
   */
  public function upsertDigest(Digest $digest) {
    $nid = $this->getNodeIdByDigestId($digest->getId());
    if (!$nid) {
      $node = Node::create([
        'type' => 'digest',
        'title' => $digest->getId(),
      ]);
    }
    elseif ($nid) {
      $node = Node::load($nid);
    }
    else {
      return NULL;
    }
    $node->title = $digest->getTitle();
    $node->field_digest_json = [
      [
        'value' => $digest->getJson(),
      ],
    ];
    $node->setOwnerId(1);
    $node->save();
    return $node;
  }

  /**
   * Deletes a digest node.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   Return entities, if found.
   */
  public function deleteDigest(Digest $digest) {
    $node_id = $this->getNodeIdByDigestId($digest->getId());
    $node = $this->entityTypeManager->getStorage('node')->load($node_id);
    if (!$node) {
      return NULL;
    }
    return $this->entityTypeManager->getStorage('node')->delete([$node]);
  }

  /**
   * Checks if node with the digest ID already exists and returns the node ID.
   */
  public function getNodeIdByDigestId(string $id) : int {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'digest')
      ->condition('field_digest_id', $id);
    $result = $query->execute();
    return !empty($result) ? reset($result) : 0;
  }

  /**
   * Get digest snippet from node.
   *
   * @return array|bool
   *   Return digest snippet, if found.
   */
  public function getDigest(EntityInterface $node, bool $preview = FALSE) {
    return json_decode($node->get('field_digest_json')->getString(), TRUE);
  }

}
