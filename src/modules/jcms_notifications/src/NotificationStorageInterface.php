<?php

namespace Drupal\jcms_notifications;

/**
 * Class MysqlNotificationStorage.
 *
 * @package Drupal\jcms_notifications
 */
interface NotificationStorageInterface {

  /**
   * Saves a node ID for notifications.
   *
   * @param int $nodeId
   *
   * @return int|null
   */
  public function saveNotificationNid(int $nodeId);

  /**
   * Gets the node IDs from the notifications table then deletes them.
   *
   * @return array
   */
  public function getNotificationNids(): array;

  /**
   * Deletes a notification node ID.
   *
   * @param int $nodeId
   *
   * @return null
   */
  public function deleteNotificationNid(int $nodeId);

  /**
   * Takes an array of node IDs and deletes them.
   *
   * @param array $nodeIds
   *
   * @return null
   */
  public function deleteNotificationNids(array $nodeIds);

}
