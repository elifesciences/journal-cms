<?php

namespace Drupal\jcms_admin\Plugin\views\field;

use Drupal\Core\Database\Database;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to present the path to the content on journal.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("jcms_content_link")
 */
class ContentLink extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    /** @var \Drupal\node\NodeInterface $content */
    $content = $values->_entity;
    $journal_path = Settings::get('journal_path');
    $journal_preview = Settings::get('journal_preview');
    $path = $this->getContentPath($content);
    if (!$path || !$journal_path) {
      return '';
    }
    else {
      $links = [
        [
          '#theme' => 'clipboard_simple',
          '#text' => $journal_path . $path,
        ],
      ];
      if ($journal_preview) {
        $links[] = [
          '#theme' => 'markup',
          '#markup' => \Drupal::l(t('Preview page'), Url::fromUri($journal_preview . $path)),
        ];
        $links = [
          '#theme' => 'item_list',
          '#items' => $links,
          '#attributes' => [
            'class' => [
              'clipboard-simple-list',
            ],
          ],
        ];
      }
      return $links;
    }
  }

  /**
   * Get content path.
   */
  private function getContentPath(NodeInterface $content) {
    $whitelist = [
      'annual_report',
      'blog_article',
      'collection',
      'event',
      'interview',
      'labs_experiment',
      'podcast_episode',
    ];
    $sns = \Drupal::service('jcms_notifications.entity_crud_notification_service');
    /** @var \Drupal\jcms_notifications\Notification\BusOutgoingMessage $message */
    if (in_array($content->bundle(), $whitelist) && $message = $sns->getMessageFromEntity($content)) {
      $stem = $message->getTopic();
      $id = $message->getId();
      $separator = '/';
      switch ($content->bundle()) {
        case 'annual_report':
          $id = NULL;
          break;

        case 'blog_article':
          $stem = 'inside-elife';
          break;

        case 'labs_experiment':
          $stem = 'labs';
          break;

        case 'podcast_episode':
          $stem = 'podcast/episode';
          $separator = '';
          break;
      }

      return sprintf('/%s', implode($separator, array_filter([$stem, $id])));
    }
    elseif ($content->bundle() === 'article') {
      return sprintf('/%s/%s', 'articles', $content->getTitle());
    }
    elseif ($content->bundle() === 'highlight_list') {
      return sprintf('/%s', $content->getTitle());
    }
    elseif ($content->bundle() === 'job_advert') {
      return sprintf('/%s/%s', 'jobs', substr($content->uuid(), -8));
    }
    elseif ($content->bundle() === 'podcast_chapter') {
      if ($episode = $this->getChapterEpisodeNumber($content)) {
        return sprintf('/%s%d#%d', 'podcast/episode', $episode, (int) $content->get('field_podcast_chapter_time')->getString());
      }
    }
    elseif ($content->bundle() === 'press_package') {
      return sprintf('/%s/%s', 'for-the-press', substr($content->uuid(), -8));
    }

    return NULL;
  }

  /**
   * Get episode number from chapter.
   */
  private function getChapterEpisodeNumber(NodeInterface $chapter) {
    $query = Database::getConnection()->select('node__field_episode_number', 'en');
    $query->addField('en', 'field_episode_number_value', 'number');
    $query->innerJoin('node__field_episode_chapter', 'ec', 'ec.entity_id = en.entity_id');
    $query->innerJoin('node', 'n', 'n.nid = ec.entity_id');
    $query->condition('ec.field_episode_chapter_target_id', $chapter->id());
    $query->range(0, 1);
    if ($episode = $query->execute()->fetchObject()) {
      return $episode->number;
    }
    else {
      return NULL;
    }
  }

}
