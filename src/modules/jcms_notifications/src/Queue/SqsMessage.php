<?php

namespace Drupal\jcms_notifications\Queue;

/**
 * Store for SQS message.
 *
 * @package Drupal\jcms_notifications\Queue
 * @todo Taken from https://github.com/elifesciences/search, split this out.
 */
final class SqsMessage implements QueueItemInterface {

  /**
   * The entity id.
   *
   * @var string
   */
  private $id;

  /**
   * The entity type.
   *
   * @var string
   */
  private $type;

  /**
   * The message body.
   *
   * @var array
   */
  private $message;

  /**
   * SQS ReceiptHandle.
   *
   * @var string
   */
  private $receipt;

  /**
   * The Message id.
   *
   * @var string
   */
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
