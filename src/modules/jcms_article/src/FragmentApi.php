<?php

namespace Drupal\jcms_article;

use Drupal\Core\Site\Settings;
use GuzzleHttp\Client;
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
   *
   * @param \GuzzleHttp\Client $client
   */
  public function __construct(Client $client) {
    $this->client = $client;
  }

  /**
   * Post the image fragment.
   *
   * @param string $articleId
   * @param string $payload
   * @return \Psr\Http\Message\ResponseInterface
   * @throws \Exception
   */
  public function postImageFragment(string $articleId, string $payload) : ResponseInterface {
    $endpoint = sprintf(Settings::get('jcms_article_fragment_images_endpoint'), $articleId);
    $response = $this->client->post($endpoint, [
      'body' => $payload,
      'headers' => [
        'Authorization' => Settings::get('jcms_article_auth_unpublished'),
        'Content-Type' => 'application/json',
      ],
      'http_errors' => FALSE,
    ]);

    \Drupal::logger('jcms_article')
      ->notice(
        'An image fragment has been posted to @endpoint with the response: @response',
        ['@endpoint' => $endpoint, '@response' => \GuzzleHttp\Psr7\str($response)]
      );

    if ($response->getStatusCode() !== Response::HTTP_OK) {
      throw new \Exception("Fragment API update could not be performed.");
    }

    return $response;
  }

  /**
   * Delete the image fragment.
   *
   * @param string $articleId
   *
   * @return \Psr\Http\Message\ResponseInterface
   * @throws \Exception
   */
  public function deleteImageFragment(string $articleId) : ResponseInterface {
    $endpoint = sprintf(Settings::get('jcms_article_fragment_images_endpoint'), $articleId);
    $response = $this->client->delete($endpoint, [
      'headers' => [
        'Authorization' => Settings::get('jcms_article_auth_unpublished'),
        'Content-Type' => 'application/json',
      ],
      'http_errors' => FALSE,
    ]);

    \Drupal::logger('jcms_article')
      ->notice(
        'An image fragment has been deleted at @endpoint with the response: @response',
        ['@endpoint' => $endpoint, '@response' => \GuzzleHttp\Psr7\str($response)]
      );

    if (!in_array($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_NOT_FOUND])) {
      throw new \Exception("Fragment API delete could not be performed.");
    }

    return $response;
  }

}
