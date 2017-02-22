<?php

namespace Drupal\jcms_article_fragment;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Article fragment entities.
 *
 * @ingroup jcms_article_fragment
 */
class ArticleFragmentListBuilder extends EntityListBuilder {

  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Article fragment ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\jcms_article_fragment\Entity\ArticleFragment */
    $row['id'] = $entity->id();
    $row['name'] = $this->l(
      $entity->label(),
      new Url(
        'entity.article_fragment.edit_form', array(
          'article_fragment' => $entity->id(),
        )
      )
    );
    return $row + parent::buildRow($entity);
  }

}
