<?php

namespace Drupal\jcms_notifications;

use Drupal\Core\Entity\EntityInterface;

/**
 * Class MysqlNotificationStorage.
 *
 * @package Drupal\jcms_notifications
 */
interface NotificationStorageInterface {

  /**
   * Saves a entity ID for notifications.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return int|null
   */
  public function saveNotificationEntityId(EntityInterface $entity);

  /**
   * Gets the entity IDs from the notifications table then deletes them.
   *
   * @return array
   */
  public function getNotificationEntityIds(): array;

  /**
   * Deletes a notification entity ID.
   *
   * @param int $entityId
   *
   * @return null
   */
  public function deleteNotificationEntityId(int $entityId);

  /**
   * Takes an array of entity IDs and deletes them.
   *
   * @param array $entityIds
   *
   * @return null
   */
  public function deleteNotificationEntityIds(array $entityIds);

}
