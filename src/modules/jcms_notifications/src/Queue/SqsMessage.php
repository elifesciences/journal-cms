<?php

namespace Drupal\jcms_notifications\Queue;

/**
 * Class SqsMessage
 *
 * @package Drupal\jcms_notifications\Queue
 * @todo Taken from https://github.com/elifesciences/search, split this out.
 */
final class SqsMessage implements QueueItem {

  private $id;
  private $type;
  private $receipt;
  private $messageId;

  public function __construct(string $messageId, string $id, string $type, string $receipt) {
    $this->messageId = $messageId;
    $this->id = $id;
    $this->type = $type;
    $this->receipt = $receipt;
  }

  /**
   * Id or Number.
   */
  public function getId() : string {
    return $this->id;
  }

  /**
   * Type (Article, Collection, Event etc.).
   */
  public function getType() : string {
    return $this->type;
  }

  /**
   * SQS ReceiptHandle.
   */
  public function getReceipt() : string {
    return $this->receipt;
  }
}
