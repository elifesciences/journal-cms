<?php

namespace Drupal\jcms_notifications;

use Drupal\Core\Entity\EntityInterface;
use Drupal\jcms_notifications\Notification\BusOutgoingMessage;

/**
 * Class EntityCrudNotificationService
 *
 * @package Drupal\jcms_notifications
 */
final class EntityCrudNotificationService {

  /**
   * A map of data types and ID keys array keyed by content type machine name.
   *
   * If any of these change please update config/aws/goaws and re-provision.
   */
  const ENTITY_TYPE_MAP = [
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
      'topic' => 'labs-posts',
      'type' => 'labs-post',
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
    'subjects' => [
      'topic' => 'subjects',
      'type' => 'subject',
      'key' => 'id',
      'field' => 'field_subject_id',
    ],
  ];

  /**
   * @var \Drupal\jcms_notifications\NotificationService
   */
  protected $notificationService;

  /**
   * EntityCrudNotificationService constructor.
   *
   * @param \Drupal\jcms_notifications\NotificationService $notification_service
   */
  public function __construct(NotificationService $notification_service) {
    $this->notificationService = $notification_service;
  }

  /**
   * Sends an SNS notification based on the node type.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return \Drupal\jcms_notifications\Notification\BusOutgoingMessage
   */
  public function sendMessage(EntityInterface $entity): BusOutgoingMessage {
    $sns_message = $this->getMessageFromEntity($entity);
    $this->notificationService->sendNotification($sns_message);
    return $sns_message;
  }

  /**
   * Takes a node object and returns an BusOutgoingMessage.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return \Drupal\jcms_notifications\Notification\BusOutgoingMessage
   */
  public function getMessageFromEntity(EntityInterface $entity): BusOutgoingMessage {
    $bundle = $entity->bundle();
    $data = self::ENTITY_TYPE_MAP[$bundle];
    $field_name = $data['field'];
    $id = $this->getIdFromEntity($entity, $field_name);
    return new BusOutgoingMessage($id, $data['key'], $data['topic'], $data['type']);
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
