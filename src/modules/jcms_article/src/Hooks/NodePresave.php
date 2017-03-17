<?php

namespace Drupal\jcms_article\Hooks;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Site\Settings;
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
    return $this->fetchArticleVersions->getArticleVersions($id);
  }

  /**
   * Adds the JSON fields to the node.
   */
  public function addJsonFields(EntityInterface $entity, $article) {
    if ($entity->get('field_article_json')->getValue()) {
      $this->updateJsonParagraph($entity, $article);
    }
    else {
      $this->createJsonParagraph($entity, $article);
    }
  }

  /**
   * Sets the status date (the date article became VOR or POA) on the node.
   */
  public function setStatusDate(EntityInterface $entity, $article) {
    $id = $entity->label();
    // Set the published date if there's a published version.
    $version = $article->getLatestPublishedVersionJson() ?: '';
    if (!$version) {
      return NULL;
    }
    $json = json_decode($version);
    if (!property_exists($json, 'statusDate')) {
      return NULL;
    }
    $date = strtotime($json->statusDate);
    $entity->set('created', $date);
  }

  /**
   * Sets the published status of the node.
   */
  public function setPublishedStatus(EntityInterface $entity, $article) {
    $id = $entity->label();
    // If there's a published version, set to published.
    $status = $article->getLatestPublishedVersionJson() ? 1 : 0;
    $entity->set('status', $status);
  }

  /**
   * Sets the article subjects on the article as taxonomy terms.
   */
  public function setSubjectTerms(EntityInterface $entity, $article) {
    $id = $entity->label();
    // Use the unpublished JSON if no published exists.
    $version = $article->getLatestPublishedVersionJson() ?: $article->getLatestUnpublishedVersionJson();
    $json = json_decode($version);
    if (is_object($json) && property_exists($json, 'subjects')) {
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
  private function updateJsonParagraph(EntityInterface $entity, $article) {
    $id = $entity->label();
    $pid = $entity->get('field_article_json')->getValue()[0]['target_id'];
    $paragraph = Paragraph::load($pid);
    $published = $article->getLatestPublishedVersionJson();
    // Store the published JSON if no unpublished exists.
    $unpublished = $article->getLatestUnpublishedVersionJson() ?: $published;
    $paragraph->set('field_article_unpublished_json', $unpublished);
    $paragraph->set('field_article_published_json', $published);
    $paragraph->save();
    $entity->field_article_json = [
      [
        'target_id' => $paragraph->id(),
        'target_revision_id' => $paragraph->getRevisionId(),
      ],
    ];
  }

  /**
   * Creates new JSON field paragraphs.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   */
  private function createJsonParagraph(EntityInterface $entity, $article) {
    $id = $entity->label();
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
    $entity->field_article_json = [
      [
        'target_id' => $paragraph->id(),
        'target_revision_id' => $paragraph->getRevisionId(),
      ],
    ];
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
    $query = \Drupal::entityQuery('taxonomy_term')
      ->condition('field_subject_id', $id);
    $tids = $query->execute();
    if ($tids) {
      $tid = reset($tids);
    }
    return $tid;
  }

  /**
   * Set alternative endpoint if we are fetching for migration only.
   */
  public function forMigrationOnly() {
    $this->fetchArticleVersions->setEndpoint(Settings::get('jcms_articles_endpoint_for_migration'));
  }

}
