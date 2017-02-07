<?php

namespace Drupal\jcms_notifications\Notification;

final class BusOutgoingMessage {

  private $id;

  private $key;

  private $topic;

  private $type;

  /**
   * BusOutgoingMessage constructor.
   *
   * @param string $id
   * @param string $key
   * @param string $topic
   * @param string $type
   */
  public function __construct(string $id, string $key, string $topic, string $type) {
    $this->id = $id;
    $this->key = $key;
    $this->topic = $topic;
    $this->type = $type;
  }

  /**
   * @return string
   */
  public function getId(): string {
    return $this->id;
  }

  /**
   * @return string
   */
  public function getKey(): string {
    return $this->key;
  }

  /**
   * @return string
   */
  public function getTopic(): string {
    return $this->topic;
  }

  /**
   * @return string
   */
  public function getType(): string {
    return $this->type;
  }

  /**
   * Returns an SNS message as a JSON string.
   *
   * @return string
   */
  public function getMessageJson(): string {
    return json_encode(['type' => $this->getType(), $this->getKey() => $this->getId()]);
  }

}
