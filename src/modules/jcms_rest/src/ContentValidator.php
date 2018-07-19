<?php

namespace Drupal\jcms_rest;

use Drupal\node\NodeInterface;
use eLife\ApiValidator\Exception\InvalidMessage;
use eLife\ApiValidator\MessageValidator;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;
use Psr\Log\LoggerInterface;

class ContentValidator
{
  private $baseUrl = 'http://journal-cms.local/';
  private $client;
  private $messageValidator;
  private $logger;

  /**
   * Constructor.
   */
  public function __construct(ClientInterface $client, MessageValidator $messageValidator, LoggerInterface $logger) {
    $this->client = $client;
    $this->messageValidator = $messageValidator;
    $this->logger = $logger;
  }

  public function setBaseUrl(string $baseUrl) {
    $this->baseUrl = rtrim($baseUrl, '/') . '/';
  }

  /**
   * @throws InvalidMessage
   */
  public function validate(NodeInterface $node, $preview = FALSE) {
    $paths = [
      'blog_article' => 'blog-articles',
      'event' => 'events',
      'interview' => 'interviews',
      'labs_experiment' => 'labs-posts',
      'press_package' => 'press-packages',
    ];
    if (array_key_exists($node->bundle(), $paths)) {
      $request = new Request('GET', $this->baseUrl . $paths[$node->bundle()] . '/' . substr($node->uuid(), -8), [
        'X-Consumer-Groups' => $preview ? 'view-unpublished-content' : 'user',
      ]);
      $response = $this->client->send($request);
      $json = json_decode($response->getBody()->getContents());
      if (!empty($json->content)) {
        try {
          $this->messageValidator->validate($response);
        } catch (InvalidMessage $message) {
          $this->logger->error($message->getMessage(), ['node' => $node->id(), 'json' => $json, 'html' => $node->get('field_content_html' . ($preview ? '_preview' : ''))->getValue()]);
          throw $message;
        }
      }
      return $json;
    }
  }

}
