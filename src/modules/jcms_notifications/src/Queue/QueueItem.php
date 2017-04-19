<?php

namespace Drupal\jcms_notifications\Queue;

interface QueueItem
{
  /**
   * Id or Number identifying the single entity the notification is related to.
   */
  public function getId() : string;
  /**
   * Type (Article, Collection, Event etc.).
   */
  public function getType() : string;
  /**
   * The message body as an array.
   */
  public function getMessage() : array;
  /**
   * SQS ReceiptHandle.
   */
  public function getReceipt() : string;
}
