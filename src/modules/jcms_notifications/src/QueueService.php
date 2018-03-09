<?php

namespace Drupal\jcms_notifications;

use Aws\AwsClientInterface;
use Aws\Result;
use Aws\Sqs\SqsClient;
use Drupal\Core\Site\Settings;
use Drupal\jcms_notifications\Queue\SqsMessage;

/**
 * Class QueueService.
 *
 * @package Drupal\jcms_notifications
 */
final class QueueService {

  /**
   * SQS client.
   *
   * @var \Aws\Sqs\SqsClient
   */
  protected $sqsClient;

  protected $endpoint = '';

  protected $queueName = '';

  protected $region = '';

  /**
   * QueueService constructor.
   */
  public function __construct(AwsClientInterface $sqs_client = NULL) {
    $this->endpoint = Settings::get('jcms_sqs_endpoint');
    $this->queueName = Settings::get('jcms_sqs_queue');
    $this->region = Settings::get('jcms_sqs_region');
    $config = [
      'profile' => 'default',
      'version' => 'latest',
      'region' => $this->region,
    ];
    if (!empty($this->endpoint)) {
      $config['endpoint'] = $this->endpoint;
    }
    $this->sqsClient = $sqs_client ?: new SqsClient($config);
  }

  /**
   * Gets the queue.
   */
  protected function getQueue() : Result {
    return $this->sqsClient->getQueueUrl([
      'QueueName' => $this->queueName,
    ]);
  }

  /**
   * Gets the article data from SQS.
   *
   * @return \Drupal\jcms_notifications\Queue\SqsMessage|null
   *   Return SQS message, if found.
   */
  public function getMessage() {
    $message = NULL;
    $queue = $this->getQueue();
    while (!$message) {
      $receiveMessage = $this->sqsClient->receiveMessage([
        'QueueUrl' => $queue['QueueUrl'],
        'VisibilityTimeout' => 60,
        'WaitTimeSeconds' => 20,
      ]);
      $response = $receiveMessage->get('Messages');
      if ($response === NULL) {
        break;
      }
      $message = $this->mapSqsMessage($response);
    }
    return $message;
  }

  /**
   * Delete a message from the queue.
   */
  public function deleteMessage(SqsMessage $sqsMessage) : Result {
    $queue = $this->getQueue();
    return $this->sqsClient->deleteMessage([
      'QueueUrl' => $queue['QueueUrl'],
      'ReceiptHandle' => $sqsMessage->getReceipt(),
    ]);
  }

  /**
   * Helper method to map values to a SqsMessage object.
   *
   * @throws \Exception
   */
  protected function mapSqsMessage(array $message) : SqsMessage {
    if (!empty($message)) {
      $message = array_shift($message);
      $message_id = $message['MessageId'] ?? '';
      $body = isset($message['Body']) ? json_decode($message['Body'], TRUE) : [];
      $id = $body['id'] ?? 0;
      $type = $body['type'] ?? 'article';
      $receipt = $message['ReceiptHandle'] ?? '';
      if ($message_id && $receipt) {
        return new SqsMessage($message_id, $id, $type, $body, $receipt);
      }
    }
    throw new \Exception('Missing arguments for SQS message.');
  }

}
