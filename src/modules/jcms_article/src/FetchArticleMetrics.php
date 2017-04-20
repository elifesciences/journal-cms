<?php

namespace Drupal\jcms_article;

use Drupal\jcms_article\Entity\ArticleMetrics;
use GuzzleHttp\Client;
use Drupal\Core\Site\Settings;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class FetchArticleMetrics.
 *
 * @package Drupal\jcms_article
 */
final class FetchArticleMetrics {

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
    $this->endpoint = Settings::get('jcms_metrics_endpoint');
    if (!$this->endpoint) {
      throw new \RuntimeException('No endpoint found for requesting article metrics.');
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
   * @return \Drupal\jcms_article\Entity\ArticleMetrics
   */
  public function getArticleMetrics(string $id): ArticleMetrics {
    $response = $this->requestArticleMetrics($id);
    // This will almost always be a string but in case it's null or something.
    $json = json_decode($response->getBody()->getContents() ?: '{}', TRUE);
    if ($response->getStatusCode() == Response::HTTP_NOT_FOUND) {
      return new ArticleMetrics($id, 0);
    }
    elseif (isset($json['totalValue'])) {
      return new ArticleMetrics($id, (int) $json['totalValue']);
    }
    throw new \TypeError('Response not formatted as expected.');
  }

  /**
   * Makes the request to get the article versions.
   *
   * @param string $id
   * @param string $type
   *
   * @return \Psr\Http\Message\ResponseInterface
   * @throws \TypeError
   */
  function requestArticleMetrics(string $id, string $type = 'page-views') {
    $options = [
      'headers' => [
        'Authorization' => Settings::get('jcms_article_auth_unpublished'),
      ],
      'http_errors' => FALSE,
    ];
    $url = $this->formatUrl($id, $type, $this->endpoint);
    $response = $this->client->get($url, $options);
    if ($response instanceof ResponseInterface) {
      return $response;
    }
    throw new \TypeError('Network connection interrupted on request.');
  }

  /**
   * Helper method to format an URL with a correct ID and type.
   *
   * @param string $id
   * @param string $type
   * @param string $url
   *
   * @return string
   */
  protected function formatUrl(string $id, string $type, string $url): string {
    return sprintf($url, $id, $type);
  }

}
