<?php

namespace Drupal\jcms_article\Entity;

/**
 * Class ArticleMetrics.
 *
 * @package Drupal\jcms_article\Entity
 */
final class ArticleMetrics {

  /**
   * Article ID.
   *
   * @var string
   */
  private $id;

  /**
   * Article page views.
   *
   * @var int
   */
  private $pageViews = 0;

  /**
   * ArticleMetrics constructor.
   */
  public function __construct(string $id, int $page_views) {
    $this->id = $id;
    $this->pageViews = $page_views;
  }

  /**
   * Returns the article ID.
   */
  public function getId(): string {
    return $this->id;
  }

  /**
   * Returns the article page-views metric.
   */
  public function getPageViews(): int {
    return $this->pageViews;
  }

}
