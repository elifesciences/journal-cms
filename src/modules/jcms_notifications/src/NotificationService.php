<?php

namespace Drupal\jcms_notifications;

use Aws\AwsClientInterface;
use Aws\Result;
use Aws\Sns\SnsClient;
use Drupal\Core\Site\Settings;
use Drupal\jcms_notifications\Notification\BusOutgoingMessage;

/**
 * SNS Notification Service.
 *
 * @package Drupal\jcms_notifications
 */
final class NotificationService {

  /**
   * SNS client.
   *
   * @var \Aws\Sns\SnsClient
   */
  protected $snsClient;

  /**
   * SNS endpoint.
   *
   * @var mixed|string
   */
  protected $endpoint = '';

  /**
   * SNS topic.
   *
   * @var mixed|string
   */
  protected $topicArn = '';

  /**
   * SNS region.
   *
   * @var mixed|string
   */
  protected $region = '';

  /**
   * NotificationService constructor.
   */
  public function __construct(AwsClientInterface $sns_client = NULL) {
    $this->endpoint = Settings::get('jcms_sqs_endpoint');
    $this->region = Settings::get('jcms_sqs_region');
    $this->topicArn = Settings::get('jcms_sns_topic_template');
    $config = [
      'profile' => 'default',
      'version' => 'latest',
      'region' => $this->region,
    ];
    if (!empty($this->endpoint)) {
      $config['endpoint'] = $this->endpoint;
    }
    $this->snsClient = $sns_client ?: new SnsClient($config);
  }

  /**
   * Sends a notification message to SNS.
   */
  public function sendNotification(BusOutgoingMessage $message) : Result {
    $topic_arn = sprintf($this->topicArn, $message->getTopic());
    return $this->snsClient->publish([
      'TopicArn' => $topic_arn,
      'Message' => $message->getMessageJson(),
    ]);
  }

}
