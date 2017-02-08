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
  public function saveNotificationNid(EntityInterface $entity) {
    $whitelist_bundles = array_keys(NodeCrudNotificationService::ENTITY_TYPE_MAP);
    if (!in_array($entity->bundle(), $whitelist_bundles)) {
      return NULL;
    }
    $id = $entity->id();
    return $this->connection->insert(self::TABLE)->fields(['node_id'], [$id])->execute();
  }

  /**
   * @inheritdoc
   */
  public function getNotificationNids(): array {
    $ids = [];
    $query = $this->connection->select(self::TABLE);
    $query->addField(self::TABLE, 'node_id');
    $result = $query->execute();
    foreach ($result->fetchAll() as $row) {
      $id = $row->node_id;
      $ids[$id] = $id;
    }
    return $ids;
  }

  /**
   * @inheritdoc
   */
  public function deleteNotificationNid(int $nodeId) {
    $this->connection->delete(self::TABLE)
      ->condition('node_id', $nodeId)
      ->execute();
  }

  /**
   * @inheritdoc
   */
  public function deleteNotificationNids(array $nodeIds) {
    foreach ($nodeIds as $nid) {
      $this->deleteNotificationNid($nid);
    }
  }

}
