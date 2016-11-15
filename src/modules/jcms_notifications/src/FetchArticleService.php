<?php

namespace Drupal\jcms_notifications;

use Drupal\Core\Site\Settings;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

/**
 * Class FetchArticleService.
 *
 * @package Drupal\jcms_notifications
 */
class FetchArticleService {

  /**
   * @var \GuzzleHttp\Client
   */
  protected $client;

  protected $endpoint;

  /**
   * FetchArticleService constructor.
   *
   * @param \GuzzleHttp\Client $client
   */
  public function __construct(Client $client) {
    $this->client = $client;
    $this->endpoint = Settings::get('jcms_articles_endpoint');
  }

  /**
   * Gets the articles by the IDs from SQS.
   *
   * @param string $id
   *
   * @return array
   */
  public function getArticle(string $id) {
    $article = [];
    if ($id) {
      $unpublished = $this->makeRequest($id, TRUE);
      if ($unpublished->getStatusCode() == 404) {
        $article = [
          'id' => $id,
          'action' => 'del',
        ];
      }
      else {
        $published = $this->makeRequest($id);
        $article = [
          'id' => $id,
          'action' => 'write',
          'data' => [
            'unpublished' => $this->getSnippet($unpublished->getBody()->getContents()),
            'published' => $published->getBody()->getContents() ? $this->getSnippet($published->getBody()->getContents()): '',
          ],
        ];
      }
    }
    return $article;
  }

  /**
   * Helper method to make the requests to get the articles.
   *
   * @param string $id
   * @param bool $unpublished
   *
   * @return \Psr\Http\Message\ResponseInterface
   * @throws \TypeError
   */
  protected function makeRequest(string $id, $unpublished = FALSE) {
    $auth_key = 'jcms_article_auth_' . ($unpublished ? 'unpublished' : 'published');
    $options = ['auth' => Settings::get($auth_key)];
    $url = $this->formatUrl($id, $this->endpoint);
    $response = $this->client->get($url, $options);
    if ($response instanceof ResponseInterface) {
      return $response;
    }
    else {
      throw new \TypeError('Network connection interrupted on request.');
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
  protected function formatUrl(string $id, string $url) : string {
    return str_replace('{id}', $id, $url);
  }

  protected function getSnippet(string $json) : string {
    $snippet = '';
    $data = json_decode($json, TRUE);
    if (is_array($data)) {
      if (isset($data['versions']) && is_array($data['versions']) && !empty($data['versions'])) {
        $snippet = json_encode(reset($data['versions']));
      }
    }
    return $snippet;
  }

}
