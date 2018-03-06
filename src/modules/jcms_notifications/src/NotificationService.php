<?php

namespace Drupal\jcms_notifications;

use Aws\AwsClientInterface;
use Aws\Result;
use Aws\Sns\SnsClient;
use Drupal\Core\Site\Settings;
use Drupal\jcms_notifications\Notification\BusOutgoingMessage;

/**
 * Class NotificationService.
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

  protected $endpoint = '';

  protected $topicArn = '';

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
