<?php

namespace Drupal\jcms_notifications\Notification;

/**
 * Object to prepare outgoing notification.
 *
 * @package Drupal\jcms_notifications\Notification
 */
final class BusOutgoingMessage {

  /**
   * Notification id.
   *
   * @var string
   */
  private $id;

  /**
   * Notification key.
   *
   * @var string
   */
  private $key;

  /**
   * Notification topic.
   *
   * @var string
   */
  private $topic;

  /**
   * Notification type.
   *
   * @var string
   */
  private $type;

  /**
   * BusOutgoingMessage constructor.
   */
  public function __construct(string $id, string $key, string $topic, string $type) {
    $this->id = $id;
    $this->key = $key;
    $this->topic = $topic;
    $this->type = $type;
  }

  /**
   * Get message ID.
   */
  public function getId(): string {
    return $this->id;
  }

  /**
   * Get message key.
   */
  public function getKey(): string {
    return $this->key;
  }

  /**
   * Get message topic.
   */
  public function getTopic(): string {
    return $this->topic;
  }

  /**
   * Get message type.
   */
  public function getType(): string {
    return $this->type;
  }

  /**
   * Returns an SNS message as a JSON string.
   */
  public function getMessageJson(): string {
    return json_encode([
      'type' => $this->getType(),
      $this->getKey() => $this->getId(),
    ]);
  }

}
