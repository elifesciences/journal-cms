<?php

namespace Drupal\jcms_article;

use Drupal\Core\Site\Settings;
use Drupal\jcms_article\Entity\ArticleMetrics;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fetch for articles metrics.
 *
 * @package Drupal\jcms_article
 */
final class FetchArticleMetrics {

  const VERSION_METRIC_TIME_PERIOD = 1;

  /**
   * GuzzleHttp\Client definition.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * Article metrics end point.
   *
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
   */
  public function setEndpoint(string $endpoint) {
    if (!empty($endpoint)) {
      $this->endpoint = $endpoint;
    }
  }

  /**
   * Gets article versions by ID.
   *
   * @throws \InvalidArgumentException
   * @throws \TypeError
   */
  public function getArticleMetrics(string $id): ArticleMetrics {
    $response = $this->requestArticleMetrics($id);
    $json = (string) $response->getBody() ?: '{}';
    $json = \GuzzleHttp\json_decode($json, TRUE);
    if ($response->getStatusCode() == Response::HTTP_NOT_FOUND) {
      return new ArticleMetrics($id, 0);
    }
    elseif (isset($json['totalValue'])) {
      return new ArticleMetrics($id, (int) $json['totalValue']);
    }
    throw new \TypeError(sprintf('Response to request for article metrics not formatted as expected: %s.', $id));
  }

  /**
   * Makes the request to get the article versions.
   *
   * @throws \GuzzleHttp\Exception\BadResponseException
   */
  private function requestArticleMetrics(string $id, string $type = 'page-views') : ResponseInterface {
    $options = [
      'headers' => [
        'Accept' => 'application/vnd.elife.metric-time-period+json;version=' . self::VERSION_METRIC_TIME_PERIOD,
      ],
    ];
    if ($auth = Settings::get('jcms_article_auth_unpublished')) {
      $options['headers'] += [
        'Authorization' => $auth,
      ];
    }
    $url = $this->formatUrl($id, $type, $this->endpoint);
    try {
      $response = $this->client->get($url, $options);
      \Drupal::logger('jcms_article')
        ->notice(
          'Article metrics have been requested @url with the response: @response',
          ['@url' => $url, '@response' => $response->getBody()->getContents()]
        );
      return $response;
    }
    catch (BadResponseException $exception) {
      if ($exception->getCode() === Response::HTTP_NOT_FOUND) {
        \Drupal::logger('jcms_article')
          ->notice(
            'Article metrics have been requested but not found @url with the response: @response',
            [
              '@url' => $url,
              '@response' => $exception->getResponse()->getBody()->getContents(),
            ]
          );
        return $exception->getResponse();
      }
      throw $exception;
    }
  }

  /**
   * Helper method to format an URL with a correct ID and type.
   */
  protected function formatUrl(string $id, string $type, string $url): string {
    return sprintf($url, $id, $type);
  }

}
