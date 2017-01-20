<?php

namespace Drupal\jcms_article\Hooks;

use Drupal\Core\Entity\EntityInterface;
use Drupal\jcms_article\FetchArticleVersions;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Class NodePresave.
 *
 * @package Drupal\jcms_article
 * @todo Look to share more code with \Drupal\jcms_article\ArticleCrud.
 */
final class NodePresave {

  /**
   * @var \Drupal\jcms_article\FetchArticleVersions
   */
  private $fetchArticleVersions;

  /**
   * @var string
   */
  private static $articleData;

  /**
   * NodePresave constructor.
   *
   * @param \Drupal\jcms_article\FetchArticleVersions $fetch_article_versions
   */
  public function __construct(FetchArticleVersions $fetch_article_versions) {
    $this->fetchArticleVersions = $fetch_article_versions;
  }

  /**
   * Gets the article data.
   *
   * @param $id
   *
   * @return \Drupal\jcms_article\Entity\ArticleVersions|null
   */
  public function getArticleById($id) {
    if (!self::$articleData) {
      self::$articleData = $this->fetchArticleVersions->getArticleVersions($id);
    }
    return self::$articleData;
  }

  /**
   * Adds the JSON fields to the node.
   */
  public function addJsonFields(EntityInterface $entity) {
    if ($entity->get('field_article_json')->getValue()) {
      $this->updateJsonParagraph($entity);
    }
    else {
      $this->createJsonParagraph($entity);
    }
  }

  /**
   * Sets the published date on the node.
   */
  public function setPublishedDate(EntityInterface $entity) {
    $id = $entity->label();
    $article = $this->getArticleById($id);
    // Use the published JSON if no unpublished exists.
    $version = $article->getLatestUnpublishedVersionJson() ?: $article->getLatestPublishedVersionJson();
    $json = json_decode($version);
    if (property_exists($json, 'versionDate')) {
      $date = strtotime($json->versionDate);
      $entity->set('created', $date);
    }
  }

  /**
   * Sets the published status of the node.
   */
  public function setPublishedStatus(EntityInterface $entity) {
    $id = $entity->label();
    $article = $this->getArticleById($id);
    // Use the published JSON if no unpublished exists.
    $version = $article->getLatestUnpublishedVersionJson() ?: $article->getLatestPublishedVersionJson();
    $json = json_decode($version);
    if (property_exists($json, 'stage')) {
      if ($json->stage == 'published') {
        $entity->set('status', 1);
      }
      else {
        $entity->set('status', 0);
      }
    }
  }

  /**
   * Sets the article subjects on the article as taxonomy terms.
   */
  public function setSubjectTerms(EntityInterface $entity) {
    $id = $entity->label();
    $article = $this->getArticleById($id);
    // Use the published JSON if no unpublished exists.
    $version = $article->getLatestUnpublishedVersionJson() ?: $article->getLatestPublishedVersionJson();
    $json = json_decode($version);
    if (property_exists($json, 'subjects')) {
      // Unset the terms first.
      $entity->set('field_subjects', []);
      foreach ($json->subjects as $subject) {
        if (isset($subject->id)) {
          $tid = $this->loadTermIdByIdField($subject->id);
          if ($tid) {
            $entity->get('field_subjects')->appendItem(['target_id' => $tid]);
          }
        }
      }
    }
  }

  /**
   * Updates existing JSON field paragraphs.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   */
  private function updateJsonParagraph(EntityInterface $entity) {
    $id = $entity->label();
    $article = $this->getArticleById($id);
    $pid = $entity->get('field_article_json')->getValue()[0]['target_id'];
    $paragraph = Paragraph::load($pid);
    $published = $article->getLatestPublishedVersionJson();
    // Store the published JSON if no unpublished exists.
    $unpublished = $article->getLatestUnpublishedVersionJson() ?: $published;
    $paragraph->set('field_article_unpublished_json', $unpublished);
    if ($published) {
      $paragraph->set('field_article_published_json', $published);
    }
  }

  /**
   * Creates new JSON field paragraphs.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   */
  private function createJsonParagraph(EntityInterface $entity) {
    $id = $entity->label();
    $article = $this->getArticleById($id);
    $published = $article->getLatestPublishedVersionJson();
    // Store the published JSON if no unpublished exists.
    $unpublished = $article->getLatestUnpublishedVersionJson() ?: $published;
    $paragraph = Paragraph::create([
      'type' => 'json',
      'field_article_published_json' => [
        'value' => $published,
      ],
      'field_article_unpublished_json' => [
        'value' => $unpublished,
      ],
    ]);
    $paragraph->save();
    $pid = $paragraph->id();
    $entity->set('field_article_json', ['target_id' => $pid]);
  }

  /**
   * Returns a taxonomy term ID, loading the term by its string ID field.
   *
   * @param string $id
   *
   * @return int
   */
  private function loadTermIdByIdField(string $id): int {
    $tid = 0;
    $query = \Drupal::entityQuery('taxonomy_term')->condition('field_subject_id', $id);
    $tids = $query->execute();
    if ($tids) {
      $tid = reset($tids);
    }
    return $tid;
  }

}
