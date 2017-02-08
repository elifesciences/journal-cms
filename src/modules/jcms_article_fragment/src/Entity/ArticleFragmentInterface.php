<?php

namespace Drupal\jcms_article_fragment\Entity;

use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Url;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Article fragment entities.
 *
 * @ingroup jcms_article_fragment
 */
interface ArticleFragmentInterface extends RevisionableInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Article fragment name.
   *
   * @return string
   *   Name of the Article fragment.
   */
  public function getName();

  /**
   * Sets the Article fragment name.
   *
   * @param string $name
   *   The Article fragment name.
   *
   * @return \Drupal\jcms_article_fragment\Entity\ArticleFragmentInterface
   *   The called Article fragment entity.
   */
  public function setName($name);

  /**
   * Gets the Article fragment creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Article fragment.
   */
  public function getCreatedTime();

  /**
   * Sets the Article fragment creation timestamp.
   *
   * @param int $timestamp
   *   The Article fragment creation timestamp.
   *
   * @return \Drupal\jcms_article_fragment\Entity\ArticleFragmentInterface
   *   The called Article fragment entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Article fragment published status indicator.
   *
   * Unpublished Article fragment are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Article fragment is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Article fragment.
   *
   * @param bool $published
   *   TRUE to set this Article fragment to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\jcms_article_fragment\Entity\ArticleFragmentInterface
   *   The called Article fragment entity.
   */
  public function setPublished($published);

  /**
   * Gets the Article fragment revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Article fragment revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\jcms_article_fragment\Entity\ArticleFragmentInterface
   *   The called Article fragment entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Article fragment revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Article fragment revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\jcms_article_fragment\Entity\ArticleFragmentInterface
   *   The called Article fragment entity.
   */
  public function setRevisionUserId($uid);

}
