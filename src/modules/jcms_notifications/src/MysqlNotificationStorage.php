<?php

namespace Drupal\jcms_notifications;

use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Core\Entity\EntityInterface;

/**
 * Class MysqlNotificationStorage.
 *
 * @package Drupal\jcms_notifications
 */
final class MysqlNotificationStorage implements NotificationStorageInterface {

  /**
   * Database table name.
   */
  const TABLE = 'jcms_notifications';

  /**
   * Notification ID field name.
   */
  const ID_FIELD = 'entity_id';

  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  private $connection;

  /**
   * MysqlNotificationStorage constructor.
   *
   * @param \Drupal\Core\Database\Driver\mysql\Connection $connection
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * @inheritdoc
   */
  public function saveNotificationEntityId(EntityInterface $entity) {
    $whitelist_bundles = array_keys(NodeCrudNotificationService::ENTITY_TYPE_MAP);
    if (!in_array($entity->bundle(), $whitelist_bundles)) {
      return NULL;
    }
    $id = $entity->id();
    return $this->connection->insert(self::TABLE)->fields([self::ID_FIELD], [$id])->execute();
  }

  /**
   * @inheritdoc
   */
  public function getNotificationEntityIds(): array {
    $ids = [];
    $query = $this->connection->select(self::TABLE);
    $query->addField(self::TABLE, self::ID_FIELD);
    $result = $query->execute();
    foreach ($result->fetchAll() as $row) {
      $id = $row->{self::ID_FIELD};
      $ids[$id] = $id;
    }
    return $ids;
  }

  /**
   * @inheritdoc
   */
  public function deleteNotificationEntityId(int $entityId) {
    $this->connection->delete(self::TABLE)
      ->condition(self::ID_FIELD, $entityId)
      ->execute();
  }

  /**
   * @inheritdoc
   */
  public function deleteNotificationEntityIds(array $entityIds) {
    foreach ($entityIds as $id) {
      $this->deleteNotificationEntityId($id);
    }
  }

}
