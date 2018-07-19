<?php

namespace Drupal\jcms_admin;

use Drupal\Core\Database\Connection;
use Drupal\jcms_notifications\EntityCrudNotificationService;
use Drupal\node\NodeInterface;

/**
 * Create content path from Node.
 */
class ContentPath {
  private $notificationService;
  private $connection;

  /**
   * Constructor.
   */
  public function __construct(EntityCrudNotificationService $notificationService, Connection $connection) {
    $this->notificationService = $notificationService;
    $this->connection = $connection;
  }

  /**
   * Create content path.
   */
  public function create(NodeInterface $node) {
    $whitelist = [
      'annual_report',
      'blog_article',
      'collection',
      'event',
      'interview',
      'labs_experiment',
      'podcast_episode',
    ];
    /** @var \Drupal\jcms_notifications\Notification\BusOutgoingMessage $message */
    if (in_array($node->bundle(), $whitelist) && $message = $this->notificationService->getMessageFromEntity($node)) {
      $stem = $message->getTopic();
      $id = $message->getId();
      $separator = '/';
      switch ($node->bundle()) {
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
    elseif ($node->bundle() === 'article') {
      return sprintf('/%s/%s', 'articles', $node->getTitle());
    }
    elseif ($node->bundle() === 'highlight_list') {
      return sprintf('/%s', $node->getTitle());
    }
    elseif ($node->bundle() === 'job_advert') {
      return sprintf('/%s/%s', 'jobs', substr($node->uuid(), -8));
    }
    elseif ($node->bundle() === 'podcast_chapter') {
      if ($episode = $this->getChapterEpisodeNumber($node)) {
        return sprintf('/%s%d#%d', 'podcast/episode', $episode, (int) $node->get('field_podcast_chapter_time')->getString());
      }
    }
    elseif ($node->bundle() === 'press_package') {
      return sprintf('/%s/%s', 'for-the-press', substr($node->uuid(), -8));
    }

    return NULL;
  }

  /**
   * Get episode number from chapter.
   */
  private function getChapterEpisodeNumber(NodeInterface $chapter) {
    $query = $this->connection->select('node__field_episode_number', 'en');
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
