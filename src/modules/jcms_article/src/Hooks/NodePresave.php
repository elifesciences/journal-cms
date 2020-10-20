<?php

namespace Drupal\jcms_article\Hooks;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Site\Settings;
use Drupal\jcms_article\Entity\ArticleVersions;
use Drupal\jcms_article\FetchArticleVersions;
use Drupal\jcms_article\FragmentApi;
use Drupal\jcms_rest\JCMSImageUriTrait;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Class NodePresave.
 *
 * @package Drupal\jcms_article
 * @todo Look to share more code with \Drupal\jcms_article\ArticleCrud.
 */
final class NodePresave {

  use JCMSImageUriTrait;

  /**
   * Store constructor argument FetchArticleVersions.
   *
   * @var \Drupal\jcms_article\FetchArticleVersions
   */
  private $fetchArticleVersions;

  /**
   * Store constructor argument FragmentApi.
   *
   * @var \Drupal\jcms_article\FragmentApi
   */
  private $fragmentApi;

  /**
   * NodePresave constructor.
   */
  public function __construct(FetchArticleVersions $fetch_article_versions, FragmentApi $fragment_api) {
    $this->fetchArticleVersions = $fetch_article_versions;
    $this->fragmentApi = $fragment_api;
  }

  /**
   * Gets the article data.
   */
  public function getArticleById(string $id) : ArticleVersions {
    return $this->fetchArticleVersions->getArticleVersions($id);
  }

  /**
   * Adds the JSON fields to the node.
   */
  public function addJsonFields(EntityInterface $entity, ArticleVersions $article) {
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
  public function setStatusDate(EntityInterface $entity, ArticleVersions $article) {
    // Set the status date if there's a published version.
    $version = $article->getLatestPublishedVersionJson() ?: '';
    if ($version) {
      $json = json_decode($version);
      if (property_exists($json, 'statusDate')) {
        $date = strtotime($json->statusDate);
        $entity->set('field_order_date', $date);
      }
    }
  }

  /**
   * Sets the published date (the date article became VOR or POA) on the node.
   */
  public function setPublishedDate(EntityInterface $entity, ArticleVersions $article) {
    // Set the published date if there's a published version.
    $version = $article->getLatestPublishedVersionJson() ?: '';
    if ($version) {
      $json = json_decode($version);
      if (property_exists($json, 'published')) {
        $date = strtotime($json->published);
        $entity->set('created', $date);
      }
    }
  }

  /**
   * Sets the published status of the node.
   */
  public function setPublishedStatus(EntityInterface $entity, ArticleVersions $article) {
    $id = $entity->label();
    // If there's a published version, set to published.
    $status = $article->getLatestPublishedVersionJson() ? 1 : 0;
    $entity->set('status', $status);
  }

  /**
   * Sets the article subjects on the article as taxonomy terms.
   */
  public function setSubjectTerms(EntityInterface $entity, ArticleVersions $article) {
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
   * Update or delete the article fragment.
   *
   * @throws \Exception
   */
  public function updateFragmentApi(EntityInterface $entity, string $articleId) {
    if (empty(Settings::get('jcms_article_auth_unpublished', FALSE))) {
      return;
    }

    $images = [];

    if ($thumbnail = $this->processFieldImage($entity->get('field_image'), FALSE, 'thumbnail', TRUE)) {
      $images['thumbnail'] = $thumbnail;
    }

    if ($socialImage = $this->processFieldImage($entity->get('field_image_social'), FALSE, 'social', TRUE)) {
      $images['social'] = $socialImage;
    }

    if (!empty($images)) {
      $this->fragmentApi->postFragment($articleId, 'image', json_encode(['image' => $images]));
    }
    else {
      $this->fragmentApi->deleteFragment($articleId, 'image');
    }
  }

  /**
   * Updates existing JSON field paragraphs.
   */
  private function updateJsonParagraph(EntityInterface $entity, ArticleVersions $article) {
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
   */
  private function createJsonParagraph(EntityInterface $entity, ArticleVersions $article) {
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
