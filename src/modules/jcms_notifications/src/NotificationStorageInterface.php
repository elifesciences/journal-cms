<?php

namespace Drupal\jcms_notifications;

use Drupal\Core\Entity\EntityInterface;

/**
 * Interface MysqlNotificationStorage.
 *
 * @package Drupal\jcms_notifications
 */
interface NotificationStorageInterface {

  /**
   * Saves a entity ID for notifications.
   *
   * @return int|null
   *   Return entity ID, if found.
   */
  public function saveNotificationEntityId(EntityInterface $entity);

  /**
   * Gets the entity IDs from the notifications table then deletes them.
   */
  public function getNotificationEntityIds(string $entityType): array;

  /**
   * Deletes a notification entity ID.
   */
  public function deleteNotificationEntityId(int $entityId) : NULL;

  /**
   * Takes an array of entity IDs and deletes them.
   */
  public function deleteNotificationEntityIds(array $entityIds) : NULL;

}
