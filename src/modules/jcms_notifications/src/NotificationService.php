<?php

namespace Drupal\jcms_notifications;

use Aws\AwsClientInterface;
use Aws\Sns\SnsClient;
use Drupal\Core\Site\Settings;

/**
 * Class NotificationService.
 *
 * @package Drupal\jcms_notifications
 */
class NotificationService {

  /**
   * @var \Aws\Sns\SnsClient
   */
  protected $snsClient;

  protected $endpoint = '';

  protected $topicArn = '';

  protected $region = '';

  /**
   * NotificationService constructor.
   *
   * @param \Aws\AwsClientInterface|NULL $sns_client
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
   *
   * @param array $message
   *
   * @return \Aws\Result
   */
  public function sendNotification(array $message) {
    $topic_arn = sprintf($this->topicArn, $message['type']);
    return $this->snsClient->publish([
      'TopicArn' => $topic_arn,
      'Message' => json_encode($message),
    ]);
  }

}
