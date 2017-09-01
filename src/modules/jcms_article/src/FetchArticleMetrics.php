<?php

namespace Drupal\jcms_article;

use Drupal\jcms_article\Entity\ArticleMetrics;
use GuzzleHttp\Client;
use Drupal\Core\Site\Settings;
use GuzzleHttp\Exception\BadResponseException;
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
   * @throws \InvalidArgumentException
   * @throws \TypeError
   */
  public function getArticleMetrics(string $id): ArticleMetrics {
    $response = $this->requestArticleMetrics($id);
    $json = (string) $response->getBody() ?: '{}';
    $json = json_decode($json, TRUE);
    if (json_last_error() !== JSON_ERROR_NONE) {
      throw new \InvalidArgumentException('JSON error: ' . json_last_error_msg());
    }
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
   * @param string $id
   * @param string $type
   *
   * @return \Psr\Http\Message\ResponseInterface
   * @throws \TypeError
   * @throws BadResponseException
   */
  private function requestArticleMetrics(string $id, string $type = 'page-views') {
    $options = [
      'headers' => [
        'Authorization' => Settings::get('jcms_article_auth_unpublished'),
      ],
    ];
    $url = $this->formatUrl($id, $type, $this->endpoint);
    try {
      $response = $this->client->get($url, $options);
      \Drupal::logger('jcms_article')
        ->notice(
          'Article metrics have been requested @url with the response: @response',
          ['@url' => $url, '@response' => \GuzzleHttp\Psr7\str($response)]
        );
      if ($response instanceof ResponseInterface) {
        return $response;
      }

      throw new \TypeError('Network connection interrupted on request.');
    }
    catch (BadResponseException $exception) {
      if ($exception->getCode() === Response::HTTP_NOT_FOUND) {
        \Drupal::logger('jcms_article')
          ->notice(
            'Article metrics have been requested but not found @url with the response: @response',
            ['@url' => $url, '@response' => \GuzzleHttp\Psr7\str($exception->getResponse())]
          );
        return $exception->getResponse();
      }
      throw $exception;
    }
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
