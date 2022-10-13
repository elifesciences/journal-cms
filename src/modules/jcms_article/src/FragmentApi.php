<?php

namespace Drupal\jcms_article;

use Drupal\Core\Site\Settings;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Message;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fetch for fragments.
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
   * Availability check.
   */
  public function available() {
    return !is_null(Settings::get('jcms_article_auth_unpublished')) && !is_null(Settings::get('jcms_article_fragments_endpoint'));
  }

  /**
   * Post a fragment to the article store.
   */
  public function postFragment(string $articleId, string $fragmentId, string $payload) {
    if (!$this->available()) {
      throw new FragmentApiUnavailable();
    }

    $endpoint = $this->endpointFullPath($articleId, $fragmentId);
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

    if ($response->getStatusCode() !== Response::HTTP_OK) {
      \Drupal::logger('jcms_article_fragment_api')
        ->error(
          'A @fragmentId fragment has been posted to @endpoint with the response: @response',
          [
            '@fragmentId' => $fragmentId,
            '@endpoint' => $endpoint,
            '@response' => Message::toString($response),
          ]
        );

      throw new FragmentApiUpdateFailure('Fragment API post could not be performed.');
    }

    \Drupal::logger('jcms_article_fragment_api')
      ->notice(
        'A @fragmentId fragment has been posted to @endpoint with the response: @response',
        [
          '@fragmentId' => $fragmentId,
          '@endpoint' => $endpoint,
          '@response' => Message::toString($response),
        ]
      );

    return $response;
  }

  /**
   * Delete a fragment from the article store.
   */
  public function deleteFragment(string $articleId, string $fragmentId) : ResponseInterface {
    if (!$this->available()) {
      throw new FragmentApiUnavailable();
    }

    $endpoint = $this->endpointFullPath($articleId, $fragmentId);
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

    if (!in_array($response->getStatusCode(),
      [
        Response::HTTP_OK,
        Response::HTTP_NOT_FOUND,
      ]
    )) {
      \Drupal::logger('jcms_article_fragment_api')
        ->error(
          'A @fragmentId fragment has been deleted at @endpoint with the response: @response',
          [
            '@fragmentId' => $fragmentId,
            '@endpoint' => $endpoint,
            '@response' => Message::toString($response),
          ]
        );

      throw new FragmentApiUpdateFailure('Fragment API delete could not be performed.');
    }

    \Drupal::logger('jcms_article_fragment_api')
      ->notice(
        'A @fragmentId fragment has been deleted at @endpoint with the response: @response',
        [
          '@fragmentId' => $fragmentId,
          '@endpoint' => $endpoint,
          '@response' => Message::toString($response),
        ]
      );

    return $response;
  }

  /**
   * Get the full path to the fragment api.
   */
  private function endpointFullPath(string $articleId, string $fragmentId) {
    return sprintf(Settings::get('jcms_article_fragments_endpoint'), $articleId, $fragmentId);
  }

}
