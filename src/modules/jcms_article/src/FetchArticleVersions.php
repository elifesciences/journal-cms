<?php

namespace Drupal\jcms_article;

use GuzzleHttp\Client;
use Drupal\Core\Site\Settings;
use Drupal\jcms_article\Entity\ArticleVersions;
use Psr\Http\Message\ResponseInterface;

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
    $action = $response->getStatusCode() == 404 ? ArticleVersions::DELETE : ArticleVersions::WRITE;
    // This will almost always be a string but in case it's null or something.
    $json = $response->getBody()->getContents() ?: '';
    return new ArticleVersions($id, $json, $action);
  }

  /**
   * Makes the request to get the article versions.
   *
   * @param string $id
   *
   * @return \Psr\Http\Message\ResponseInterface
   * @throws \TypeError
   */
  function requestArticleVersions(string $id): ResponseInterface {
    $options = [
      'headers' => [
        'Authorization' => Settings::get('jcms_article_auth_unpublished'),
      ],
      'http_errors' => FALSE,
    ];
    $url = $this->formatUrl($id, $this->endpoint);
    $response = $this->client->get($url, $options);
    if ($response instanceof ResponseInterface) {
      return $response;
    }
    throw new \TypeError('Network connection interrupted on request.');
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
