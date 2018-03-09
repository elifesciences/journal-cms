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
   * Entity type field name.
   */
  const ENTITY_TYPE_FIELD = 'entity_type';

  /**
   * Entity ID field name.
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
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public function saveNotificationEntityId(EntityInterface $entity) {
    $whitelist_bundles = array_keys(EntityCrudNotificationService::ENTITY_TYPE_MAP);
    if (!in_array($entity->bundle(), $whitelist_bundles)) {
      return NULL;
    }
    $id = $entity->id();
    $entity_type = $entity->getEntityTypeId();
    return $this->connection->insert(self::TABLE)->fields([self::ID_FIELD => $id, self::ENTITY_TYPE_FIELD => $entity_type])->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getNotificationEntityIds(string $entityType = 'node'): array {
    $ids = [];
    $query = $this->connection->select(self::TABLE);
    $query->condition(self::ENTITY_TYPE_FIELD, $entityType);
    $query->addField(self::TABLE, self::ID_FIELD);
    $result = $query->execute();
    foreach ($result->fetchAll() as $row) {
      $id = $row->{self::ID_FIELD};
      $ids[$id] = $id;
    }
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteNotificationEntityId(int $entityId) {
    $this->connection->delete(self::TABLE)
      ->condition(self::ID_FIELD, $entityId)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function deleteNotificationEntityIds(array $entityIds) {
    foreach ($entityIds as $id) {
      $this->deleteNotificationEntityId($id);
    }
  }

}
