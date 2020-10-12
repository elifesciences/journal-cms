<?php

namespace Drupal\jcms_article;

use Drupal\Core\Site\Settings;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Message;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class FragmentApi.
 *
 * @package Drupal\jcms_article
 */
class FragmentApi {

  /**
   * GuzzleHttp\Client definition.
   *
   * @var \GuzzleHttp\Client
   */
  private $client;

  /**
   * FragmentApi constructor.
   */
  public function __construct(Client $client) {
    $this->client = $client;
  }

  /**
   * Post a fragment to the article store.
   *
   * @throws Exception
   */
  public function postFragment(string $fragmentPath, string $articleId, string $payload) {
    $endpoint = $this->endpointFullPath($articleId, $fragmentPath);
    $options = [
      'body' => $payload,
      'headers' => [
        'Content-Type' => 'application/json',
      ],
      'http_errors' => FALSE,
    ];
    if ($auth = Settings::get('jcms_article_auth_unpublished')) {
      $options['headers']['Authorization'] = $auth;
    }
    $response = $this->client->post($endpoint, $options);

    \Drupal::logger('jcms_article')
      ->notice(
        'A @fragmentPath fragment has been posted to @endpoint with the response: @response',
        [
          '@fragmentPath' => $fragmentPath,
          '@endpoint' => $endpoint,
          '@response' => Message::toString($response),
        ]
      );

    if ($response->getStatusCode() !== Response::HTTP_OK) {
      throw new Exception('Fragment API update could not be performed.');
    }

    return $response;
  }

  /**
   * Delete a fragment from the article store.
   *
   * @throws Exception
   */
  public function deleteFragment(string $fragmentPath, string $articleId) : ResponseInterface {
    $endpoint = $this->endpointFullPath($articleId, $fragmentPath);
    $options = [
      'headers' => [
        'Content-Type' => 'application/json',
      ],
      'http_errors' => FALSE,
    ];
    if ($auth = Settings::get('jcms_article_auth_unpublished')) {
      $options['headers']['Authorization'] = $auth;
    }

    $response = $this->client->delete($endpoint, $options);

    \Drupal::logger('jcms_article')
      ->notice(
        'A @fragmentPath fragment has been deleted at @endpoint with the response: @response',
        [
          '@fragmentPath' => $fragmentPath,
          '@endpoint' => $endpoint,
          '@response' => Message::toString($response),
        ]
      );

    if (!in_array($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_NOT_FOUND])) {
      throw new Exception("Fragment API delete could not be performed.");
    }

    return $response;
  }

  /**
   * Get the full path to the fragment api.
   */
  private function endpointFullPath(string $articleId, string $path) {
    return sprintf(Settings::get('jcms_article_fragments_endpoint'), $articleId) . $path;
  }

}
