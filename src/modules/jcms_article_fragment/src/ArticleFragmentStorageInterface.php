<?php

namespace Drupal\jcms_article_fragment;

use Drupal\Core\Entity\ContentEntityStorageInterface;
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
interface ArticleFragmentStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Article fragment revision IDs for a specific Article fragment.
   *
   * @param \Drupal\jcms_article_fragment\Entity\ArticleFragmentInterface $entity
   *   The Article fragment entity.
   *
   * @return int[]
   *   Article fragment revision IDs (in ascending order).
   */
  public function revisionIds(ArticleFragmentInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Article fragment author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Article fragment revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\jcms_article_fragment\Entity\ArticleFragmentInterface $entity
   *   The Article fragment entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(ArticleFragmentInterface $entity);

  /**
   * Unsets the language for all Article fragment with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
