<?php

namespace Drupal\jcms_article_fragment\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\jcms_article_fragment\Entity\ArticleFragmentInterface;

/**
 * Class ArticleFragmentController.
 *
 *  Returns responses for Article fragment routes.
 *
 * @package Drupal\jcms_article_fragment\Controller
 */
class ArticleFragmentController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Displays a Article fragment  revision.
   *
   * @param int $article_fragment_revision
   *   The Article fragment  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($article_fragment_revision) {
    $article_fragment = $this->entityManager()->getStorage('article_fragment')->loadRevision($article_fragment_revision);
    $view_builder = $this->entityManager()->getViewBuilder('article_fragment');

    return $view_builder->view($article_fragment);
  }

  /**
   * Page title callback for a Article fragment  revision.
   *
   * @param int $article_fragment_revision
   *   The Article fragment  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($article_fragment_revision) {
    $article_fragment = $this->entityManager()->getStorage('article_fragment')->loadRevision($article_fragment_revision);
    return $this->t('Revision of %title from %date', array('%title' => $article_fragment->label(), '%date' => format_date($article_fragment->getRevisionCreationTime())));
  }

  /**
   * Generates an overview table of older revisions of a Article fragment .
   *
   * @param \Drupal\jcms_article_fragment\Entity\ArticleFragmentInterface $article_fragment
   *   A Article fragment  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(ArticleFragmentInterface $article_fragment) {
    $account = $this->currentUser();
    $langcode = $article_fragment->language()->getId();
    $langname = $article_fragment->language()->getName();
    $languages = $article_fragment->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $article_fragment_storage = $this->entityManager()->getStorage('article_fragment');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $article_fragment->label()]) : $this->t('Revisions for %title', ['%title' => $article_fragment->label()]);
    $header = array($this->t('Revision'), $this->t('Operations'));

    $revert_permission = (($account->hasPermission("revert all article fragment revisions") || $account->hasPermission('administer article fragment entities')));
    //$delete_permission = (($account->hasPermission("delete all article fragment revisions") || $account->hasPermission('administer article fragment entities')));

    $rows = array();

    $vids = $article_fragment_storage->revisionIds($article_fragment);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\jcms_article_fragment\ArticleFragmentInterface $revision */
      $revision = $article_fragment_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')->format($revision->revision_timestamp->value, 'short');
        if ($vid != $article_fragment->getRevisionId()) {
          $link = $this->l($date, new Url('entity.article_fragment.revision', ['article_fragment' => $article_fragment->id(), 'article_fragment_revision' => $vid]));
        }
        else {
          $link = $article_fragment->link($date);
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => \Drupal::service('renderer')->renderPlain($username),
              'message' => ['#markup' => $revision->revision_log_message->value, '#allowed_tags' => Xss::getHtmlTagList()],
            ],
          ],
        ];
        $row[] = $column;

        if ($latest_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }
          $latest_revision = FALSE;
        }
        else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => $has_translations ?
              Url::fromRoute('article_fragment.revision_revert_translation_confirm', ['article_fragment' => $article_fragment->id(), 'article_fragment_revision' => $vid, 'langcode' => $langcode]) :
              Url::fromRoute('article_fragment.revision_revert_confirm', ['article_fragment' => $article_fragment->id(), 'article_fragment_revision' => $vid]),
            ];
          }

          //if ($delete_permission) {
          //  $links['delete'] = [
          //    'title' => $this->t('Delete'),
          //    'url' => Url::fromRoute('article_fragment.revision_delete_confirm', ['article_fragment' => $article_fragment->id(), 'article_fragment_revision' => $vid]),
          //  ];
          //}

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
      }
    }

    $build['article_fragment_revisions_table'] = array(
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    );

    return $build;
  }

}
