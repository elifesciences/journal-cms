<?php

namespace Drupal\jcms_article_fragment;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\jcms_article_fragment\Entity\ArticleFragmentInterface;

/**
 * Defines the storage handler class for Article fragment entities.
 *
 * This extends the base storage class, adding required special handling for
 * Article fragment entities.
 *
 * @ingroup jcms_article_fragment
 */
class ArticleFragmentStorage extends SqlContentEntityStorage implements ArticleFragmentStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(ArticleFragmentInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {article_fragment_revision} WHERE id=:id ORDER BY vid',
      array(':id' => $entity->id())
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {article_fragment_field_revision} WHERE uid = :uid ORDER BY vid',
      array(':uid' => $account->id())
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(ArticleFragmentInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {article_fragment_field_revision} WHERE id = :id AND default_langcode = 1', array(':id' => $entity->id()))
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('article_fragment_revision')
      ->fields(array('langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED))
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
