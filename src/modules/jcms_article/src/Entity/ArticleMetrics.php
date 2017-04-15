<?php

namespace Drupal\jcms_article\Entity;

/**
 * Class ArticleMetrics
 *
 * @package Drupal\jcms_article\Entity
 */
final class ArticleMetrics {

  /**
   * @var string
   */
  private $id;

  /**
   * @var int
   */
  private $pageViews = 0;

  /**
   * ArticleMetrics constructor.
   *
   * @param string $id
   * @param int $page_views
   */
  public function __construct(string $id, int $page_views) {
    $this->id = $id;
    $this->pageViews = $page_views;
  }

  /**
   * Returns the article ID.
   *
   * @return string
   */
  public function getId(): string {
    return $this->id;
  }

  /**
   * Returns the article page-views metric.
   *
   * @return int
   */
  public function getPageViews(): int {
    return $this->pageViews;
  }

}
