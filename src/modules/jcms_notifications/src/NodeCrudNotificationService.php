<?php

namespace Drupal\jcms_notifications;

use Drupal\Core\Entity\EntityInterface;

/**
 * Class NodeCrudNotificationService
 *
 * @package Drupal\jcms_notifications
 */
class NodeCrudNotificationService {

  /**
   * @var \Drupal\jcms_notifications\NotificationService
   */
  protected $notificationService;

  /**
   * NodeCrudNotificationService constructor.
   *
   * @param \Drupal\jcms_notifications\NotificationService $notification_service
   */
  public function __construct(NotificationService $notification_service) {
    $this->notificationService = $notification_service;
  }

  /**
   * Main class method - sends a notification based on the node type.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   */
  public function action(EntityInterface $entity) {
    $bundle = $entity->bundle();
    $bundles = array_keys($this->entityTypeMap());
    if (in_array($bundle, $bundles)) {
      $node_data = $this->entityTypeMap()[$bundle];
      $topic = $node_data['topic'];
      $key = $node_data['key'];
      $field_name = $node_data['field'];
      $id = $this->getIdFromEntity($entity, $field_name);
      $type = $node_data['type'];
      $message = ['type' => $type, $key => $id];
      $this->notificationService->sendNotification($topic, $message);
    }
  }

  /**
   * A map of data types and ID keys array keyed by content type machine name.
   *
   * If any of these change please update config/aws/goaws and re-provision.
   *
   * @return array
   */
  public function entityTypeMap() {
    return [
      'annual_report' => [
        'topic' => 'annual-reports',
        'type' => 'annual-report',
        'key' => 'year',
        'field' => 'field_annual_report_year',
      ],
      'blog_article' => [
        'topic' => 'blog-articles',
        'type' => 'blog-article',
        'key' => 'id',
        'field' => 'uuid_last_8',
      ],
      'collection' => [
        'topic' => 'collections',
        'type' => 'collection',
        'key' => 'id',
        'field' => 'uuid_last_8',
      ],
      'cover' => [
        'topic' => 'covers',
        'type' => 'cover',
        'key' => 'id',
        'field' => 'uuid_last_8',
      ],
      'event' => [
        'topic' => 'events',
        'type' => 'event',
        'key' => 'id',
        'field' => 'uuid_last_8',
      ],
      'interview' => [
        'topic' => 'interviews',
        'type' => 'interview',
        'key' => 'id',
        'field' => 'uuid_last_8',
      ],
      'labs_experiment' => [
        'topic' => 'labs-experiments',
        'type' => 'labs-experiment',
        'key' => 'id',
        'field' => 'uuid_last_8',
      ],
      'person' => [
        'topic' => 'people',
        'type' => 'person',
        'key' => 'id',
        'field' => 'uuid_last_8',
      ],
      'podcast_episode' => [
        'topic' => 'podcast-episodes',
        'type' => 'podcast-episode',
        'key' => 'number',
        'field' => 'field_episode_number',
      ],
      'subject' => [
        'topic' => 'subjects',
        'type' => 'subject',
        'key' => 'id',
        'field' => 'field_subject_id',
      ],
    ];
  }

  /**
   * Gets the ID from a node entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param string $field_name
   *
   * @return string
   */
  public function getIdFromEntity(EntityInterface $entity, string $field_name) {
    if ($field_name == 'entity_id') {
      return $entity->id();
    }
    elseif ($field_name == 'uuid_last_8') {
      return substr($entity->uuid(), -8, 8);
    }
    elseif (strpos($field_name, 'field_') === 0) {
      return $entity->get($field_name)->getValue()[0]['value'];
    }
    return '';
  }

}
