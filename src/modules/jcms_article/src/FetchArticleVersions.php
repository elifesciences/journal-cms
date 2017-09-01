<?php

namespace Drupal\jcms_article;

use GuzzleHttp\Client;
use Drupal\Core\Site\Settings;
use Drupal\jcms_article\Entity\ArticleVersions;
use GuzzleHttp\Exception\BadResponseException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class FetchArticleVersions.
 *
 * @package Drupal\jcms_article
 */
final class FetchArticleVersions {

  /**
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * @var string
   */
  protected $endpoint;

  /**
   * Constructor.
   */
  public function __construct(Client $client) {
    $this->client = $client;
    $this->endpoint = Settings::get('jcms_articles_endpoint');
    if (!$this->endpoint) {
      throw new \RuntimeException('No endpoint found for requesting article versions.');
    }
  }

  /**
   * Allow an alternate endpoint to be set.
   *
   * @param $endpoint
   */
  public function setEndpoint($endpoint) {
    if (!empty($endpoint)) {
      $this->endpoint = $endpoint;
    }
  }

  /**
   * Gets article versions by ID.
   *
   * @param string $id
   *
   * @return \Drupal\jcms_article\Entity\ArticleVersions
   */
  public function getArticleVersions(string $id): ArticleVersions {
    $response = $this->requestArticleVersions($id);
    $action = $response->getStatusCode() == Response::HTTP_NOT_FOUND ? ArticleVersions::DELETE : ArticleVersions::WRITE;
    // This will almost always be a string but in case it's null or something.
    return new ArticleVersions($id, (string) $response->getBody(), $action);
  }

  /**
   * Makes the request to get the article versions.
   *
   * @param string $id
   *
   * @return \Psr\Http\Message\ResponseInterface
   * @throws \TypeError
   */
  private function requestArticleVersions(string $id): ResponseInterface {
    $options = [
      'headers' => [
        'Authorization' => Settings::get('jcms_article_auth_unpublished'),
      ],
    ];
    $url = $this->formatUrl($id, $this->endpoint);
    try {
      $response = $this->client->get($url, $options);
      if ($response instanceof ResponseInterface) {
        \Drupal::logger('jcms_article')
          ->notice(
            'Article versions have been requested @url with the response: @response',
            ['@url' => $url, '@response' => \GuzzleHttp\Psr7\str($response)]
          );
        return $response;
      }
      throw new \TypeError('Network connection interrupted on request.');
    }
    catch (BadResponseException $exception) {
      if ($exception->getCode() === Response::HTTP_NOT_FOUND) {
        \Drupal::logger('jcms_article')
          ->notice(
            'Article versions have been requested but not found @url with the response: @response',
            ['@url' => $url, '@response' => \GuzzleHttp\Psr7\str($exception->getResponse())]
          );
        return $exception->getResponse();
      }
      throw $exception;
    }
  }

  /**
   * Helper method to format an URL with a correct ID.
   *
   * @param string $id
   * @param string $url
   *
   * @return string
   */
  protected function formatUrl(string $id, string $url): string {
    return sprintf($url, $id);
  }

}
