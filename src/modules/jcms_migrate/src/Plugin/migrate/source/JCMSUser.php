<?php

namespace Drupal\jcms_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * Source plugin for users.
 *
 * @MigrateSource(
 *   id = "jcms_user"
 * )
 */
class JCMSUser extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('users', 'u')
      ->fields('u', ['uid', 'name', 'pass', 'status', 'mail', 'created', 'login', 'access', 'uuid']);
    $query->leftJoin('users_roles', 'ur', 'ur.uid = u.uid');
    $query->innerJoin('role', 'r', 'r.rid = ur.rid');
    $query->addExpression('GROUP_CONCAT(r.name)', 'roles');

    $query->condition('u.status', 1);
    $query->groupBy('u.uid');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'uid' => $this->t('Legacy ID'),
      'uuid' => $this->t('UUID'),
      'name' => $this->t('User name'),
      'pass' => $this->t('Password'),
      'mail' => $this->t('Email'),
      'created' => $this->t('Created timestamp'),
      'login' => $this->t('Login timestamp'),
      'access' => $this->t('Access timestamp'),
      'status' => $this->t('Roles'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'uid' => [
        'type' => 'integer',
        'alias' => 'u',
      ],
    ];
  }

}
