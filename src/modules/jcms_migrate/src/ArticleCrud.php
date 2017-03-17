<?php

namespace Drupal\jcms_migrate;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\jcms_article\Entity\ArticleVersions;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Class ArticleCrud
 *
 * @package Drupal\jcms_migrate
 */
class ArticleCrud extends \Drupal\jcms_article\ArticleCrud {

  /**
   * ArticleCrud constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   */
  public function __construct(EntityTypeManager $entity_type_manager) {
    parent::__construct($entity_type_manager);
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
    // Set flag for article nodes created during migration.
    $node->migration = TRUE;
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

}
