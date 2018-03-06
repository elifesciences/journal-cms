<?php

namespace Drupal\jcms_notifications\Queue;

/**
 * Class SqsMessage.
 *
 * @package Drupal\jcms_notifications\Queue
 * @todo Taken from https://github.com/elifesciences/search, split this out.
 */
final class SqsMessage implements QueueItemInterface {

  private $id;
  private $type;
  private $message;
  private $receipt;
  private $messageId;

  /**
   * SqsMessage constructor.
   */
  public function __construct(string $messageId, string $id, string $type, array $message, string $receipt) {
    $this->messageId = $messageId;
    $this->id = $id;
    $this->type = $type;
    $this->message = $message;
    $this->receipt = $receipt;
  }

  /**
   * Identifier for the SQS message.
   */
  public function getMessageId() : string {
    return $this->messageId;
  }

  /**
   * {@inheritdoc}
   */
  public function getId() : string {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() : string {
    return $this->type;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage() : array {
    return $this->message;
  }

  /**
   * {@inheritdoc}
   */
  public function getReceipt() : string {
    return $this->receipt;
  }

}
