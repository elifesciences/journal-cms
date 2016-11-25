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
    $types = array_keys($this->entityTypeMap());
    if (in_array($bundle, $types)) {
      $node_data = $this->entityTypeMap()[$bundle];
      $key = $node_data['key'];
      $field_name = $node_data['field'];
      $id = $this->getIdFromEntity($entity, $field_name);
      $type = $node_data['type'];
      $message = ['type' => $type, $key => $id];
      $this->notificationService->sendNotification($message);
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
        'type' => 'annual-reports',
        'key' => 'year',
        'field' => 'field_annual_report_year',
      ],
      'blog_article' => [
        'type' => 'blog-articles',
        'key' => 'id',
        'field' => 'uuid_last_8',
      ],
      'collection' => [
        'type' => 'collections',
        'key' => 'id',
        'field' => 'uuid_last_8',
      ],
      'event' => [
        'type' => 'events',
        'key' => 'id',
        'field' => 'uuid_last_8',
      ],
      'interview' => [
        'type' => 'interviews',
        'key' => 'id',
        'field' => 'uuid_last_8',
      ],
      'person' => [
        'type' => 'people',
        'key' => 'id',
        'field' => 'uuid_last_8',
      ],
      'podcast_episode' => [
        'type' => 'podcast-episodes',
        'key' => 'number',
        'field' => 'field_episode_number',
      ],
      'subjects' => [
        'type' => 'subjects',
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
