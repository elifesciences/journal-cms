<?php

namespace Drupal\jcms_notifications;

use Drupal\Core\Entity\EntityInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\taxonomy\Entity\Term;

/**
 * Class ArticlePresave.
 *
 * @package Drupal\jcms_notifications
 */
class ArticlePresave {

  /**
   * @var \Drupal\jcms_notifications\FetchArticleService
   */
  protected $fetchArticle;

  /**
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $node;

  /**
   * @var null
   */
  protected static $articleData = NULL;

  /**
   * ArticlePresave constructor.
   *
   * @param \Drupal\jcms_notifications\FetchArticleService $fetch_article
   */
  public function __construct(FetchArticleService $fetch_article) {
    $this->fetchArticle = $fetch_article;
  }

  /**
   * Sets the node property.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   */
  public function setNode(EntityInterface $entity) {
    $this->node = $entity;
  }

  /**
   * Gets the node property.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   */
  public function getNode() {
    return $this->node;
  }

  /**
   * Returns true if the node is an article.
   *
   * @return bool
   */
  public function isArticle() {
    return ($this->node->bundle() == 'article');
  }

  /**
   * Returns true if the node is new.
   *
   * @return bool
   */
  public function isNew() {
    return $this->node->isNew();
  }

  /**
   * Checks if the node is valid.
   *
   * @return bool
   */
  public function nodeIsValid() {
    if (!($this->getNode() instanceof EntityInterface)) {
      return FALSE;
    }
    if (!$this->isArticle()) {
      return FALSE;
    }
    if ($this->isNew()) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Gets the article ID from the node.
   *
   * @return null|string
   */
  public function getArticleIdFromNode() {
    return $this->getNode()->label();
  }

  /**
   * Gets the article data.
   *
   * @param $id
   *
   * @return array|null
   */
  public function getArticleById($id) {
    if (self::$articleData !== NULL) {
      self::$articleData = $this->fetchArticle->getArticle($id);
    }
    return self::$articleData;
  }

  /**
   * Adds the JSON fields to the node.
   */
  public function addJsonFields() {
    if ($this->nodeIsValid()) {
      $id = $this->getArticleIdFromNode();
      $article = $this->getArticleById($id);
      $pid = $this->getNode()->get('field_article_json')->getValue()[0]['target_id'];
      $paragraph = Paragraph::load($pid);
      $paragraph->set('field_article_unpublished_json', $article['data']['unpublished']);
      if ($article['data']['published']) {
        $paragraph->set('field_article_published_json', $article['data']['published']);
      }
    }
  }

  /**
   * Sets the published date on the node.
   */
  public function setPublishedDate() {
    if ($this->nodeIsValid()) {
      $id = $this->getArticleIdFromNode();
      $article = $this->getArticleById($id);
      // Whether new or not, set the published date.
      $json = json_decode($article['data']['unpublished']);
      if (is_numeric($json['published'])) {
        $date = date('U', $json['published']);
        $this->getNode()->set('created', $date);
      }
    }
  }

  /**
   * Sets the published status of the node.
   */
  public function setPublishedStatus() {
    if ($this->nodeIsValid()) {
      $id = $this->getArticleIdFromNode();
      $article = $this->getArticleById($id);
      $json = json_decode($article['data']['unpublished']);
      $stage = $json['stage'] ?? '';
      if ($stage == 'published') {
        $this->getNode()->set('status', 1);
      }
      else {
        $this->getNode()->set('status', 0);
      }
    }
  }

  /**
   * Sets the article subjects on the article as taxonomy terms.
   */
  public function setSubjectTerms() {
    if ($this->nodeIsValid()) {
      $id = $this->getArticleIdFromNode();
      $article = $this->getArticleById($id);
      // Whether new or not, set the published date.
      $json = json_decode($article['data']['unpublished']);
      if ($json['subjects']) {
        foreach ($json['subjects'] as $subject) {
          if (isset($subject['id'])) {
            $tid = $this->loadTermIdByIdField($subject['id']);
            if ($tid) {
              $this->getNode()->get('field_subjects')->appendItem(['target_id' => $tid]);
            }
          }
        }
      }
    }
  }

  /**
   * Returns a taxonomy term ID, loading the term by its string ID field.
   *
   * @param string $id
   *
   * @return int
   */
  public function loadTermIdByIdField(string $id) : int {
    $tid = 0;
    $query = \Drupal::entityQuery('term')->condition('field_subject_id', $id);
    $tids = $query->execute();
    if ($tids) {
      $tid = reset($tids);
    }
    return $tid;
  }

}
