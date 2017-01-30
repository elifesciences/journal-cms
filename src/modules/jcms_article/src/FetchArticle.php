<?php

namespace Drupal\jcms_article;

use Drupal\Core\Site\Settings;
use Drupal\jcms_article\Entity\Article;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

/**
 * Class FetchArticle.
 *
 * @package Drupal\jcms_article
 */
class FetchArticle {

  /**
   * GuzzleHttp\Client definition.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * Retrieval limit.
   *
   * @var int|NULL
   */
  protected $limit = NULL;

  /**
   * Constructor.
   */
  public function __construct(Client $client) {
    $this->client = $client;
  }

  /**
   * Set retrieval limit for articles.
   *
   * @param int|NULL $limit
   */
  public function setLimit($limit) {
    $this->limit = $limit ?: NULL;
  }

  /**
   * Gets article versions by ID.
   *
   * @param string $id
   *
   * @return \Drupal\jcms_article\Entity\Article
   */
  public function getArticleById(string $id): Article {
    $response = $this->requestArticle($id);
    // This will almost always be a string but in case it's null or something.
    $json = $response->getBody()->getContents() ?: '';
    return new Article($id, $json);
  }

  /**
   * Makes the request to get the article versions.
   *
   * @param string $id
   *
   * @return \Psr\Http\Message\ResponseInterface
   * @throws \TypeError
   */
  function requestArticle(string $id): ResponseInterface {
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
   * Gets the IDs for every article in Lax.
   *
   * @return array
   */
  public function getAllArticleIds() {
    $ids = [];
    $articles = $this->getAllArticles();
    if ($articles) {
      foreach ($articles as $article) {
        $ids[] = $article->getId();
      }
    }
    return array_values($ids);
  }

  /**
   * Gets every article in Lax.
   *
   * @return array
   */
  public function getAllArticles() {
    $articles = [];
    $endpoint = Settings::get('jcms_all_articles_endpoint');
    if ($endpoint) {
      $stop = FALSE;
      $page = 1;
      $per_page = 100;
      $options = [
        'headers' => [
          'Authorization' => Settings::get('jcms_article_auth_unpublished'),
        ],
        'http_errors' => FALSE,
      ];
      while (!$stop) {
        $response = $this->client->get($endpoint, $options + ['query' => ['per-page' => $per_page, 'page' => $page]]);
        if ($response instanceof ResponseInterface) {
          $body = $response->getBody()->getContents();
          $json = json_decode($body, TRUE);
          if (isset($json['items']) && !empty($json['items'])) {
            foreach ($json['items'] as $data) {
              if (isset($data['id'])) {
                $articles[$data['id']] = new Article($data['id'], json_encode($data));
                if (!empty($this->limit) && count($articles) >= $this->limit) {
                  return $articles;
                }
              }
            }
          }
          else {
            $stop = TRUE;
          }
        }
        else {
          $stop = TRUE;
        }
        $page++;
      }
    }
    return $articles;
  }

}
