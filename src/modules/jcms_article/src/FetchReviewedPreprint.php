<?php

namespace Drupal\jcms_article;

use Drupal\Core\Site\Settings;
use Drupal\jcms_article\Entity\ReviewedPreprint;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

/**
 * Fetch ReviewedPreprint Snippets.
 *
 * @package Drupal\jcms_article
 */
class FetchReviewedPreprint {

  const VERSION_REVIEWED_PREPRINT = 1;
  const VERSION_REVIEWED_PREPRINT_LIST = 1;

  /**
   * GuzzleHttp\Client definition.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * Retrieval limit.
   *
   * @var int|null
   */
  protected $limit = NULL;

  /**
   * Constructor.
   */
  public function __construct(Client $client) {
    $this->client = $client;
  }

  /**
   * Set retrieval limit for reviewed preprints.
   *
   * @param int|null $limit
   *   Limit, if set.
   */
  public function setLimit($limit) {
    $this->limit = $limit ?: NULL;
  }

  /**
   * Gets reviewed preprint by ID.
   */
  public function getReviewedPreprintById(string $id): ReviewedPreprint {
    $response = $this->requestReviewedPreprint($id);
    return new ReviewedPreprint($id, (string) $response->getBody());
  }

  /**
   * Makes the request to get the reviewed preprint.
   *
   * @throws \TypeError
   */
  public function requestReviewedPreprint(string $id): ResponseInterface {
    $options = [
      'http_errors' => FALSE,
      'headers' => [
        'Accept' => 'application/vnd.elife.reviewed-preprint+json;version=' . self::VERSION_REVIEWED_PREPRINT,
      ],
    ];
    if ($auth = Settings::get('jcms_article_auth_unpublished')) {
      $options['headers'] += [
        'Authorization' => $auth,
      ];
    }
    $url = Settings::get('jcms_all_reviewed_preprints_endpoint') . '/' . $id;
    $response = $this->client->get($url, $options);
    if ($response instanceof ResponseInterface) {
      \Drupal::logger('jcms_reviewed_preprint')
        ->notice(
          'ReviewedPreprint has been requested @url with the response: @response',
          ['@url' => $url, '@response' => $response->getBody()->getContents()]
        );
      return $response;
    }
    throw new \TypeError('Network connection interrupted on request.');
  }

  /**
   * Gets the IDs for every reviewed preprint.
   */
  public function getAllReviewedPreprintIds() : array {
    $ids = [];
    $reviewed_preprints = $this->getAllReviewedPreprints();
    if ($reviewed_preprints) {
      foreach ($reviewed_preprints as $reviewed_preprint) {
        $ids[] = $reviewed_preprint->getId();
      }
    }
    return array_values($ids);
  }

  /**
   * Gets every reviewed preprints.
   */
  public function getAllReviewedPreprints($start_date = NULL) : array {
    $reviewed_preprints = [];
    $endpoint = Settings::get('jcms_all_reviewed_preprints_endpoint');
    if ($endpoint) {
      $stop = FALSE;
      $page = 1;
      $per_page = 100;
      $options = [
        'http_errors' => FALSE,
        'headers' => [
          'Accept' => 'application/vnd.elife.reviewed-preprint-list+json;version=' . self::VERSION_REVIEWED_PREPRINT_LIST,
        ],
      ];
      if ($auth = Settings::get('jcms_article_auth_unpublished')) {
        $options['headers'] += [
          'Authorization' => $auth,
        ];
      }
      while (!$stop) {
        $response = $this->client->get($endpoint, $options + [
          'query' => array_filter([
            'per-page' => $per_page,
            'page' => $page,
            'start-date' => $start_date,
          ]),
        ]);
        if ($response instanceof ResponseInterface) {
          $json = json_decode((string) $response->getBody(), TRUE);
          if (isset($json['items']) && !empty($json['items'])) {
            foreach ($json['items'] as $data) {
              if (isset($data['id'])) {
                $reviewed_preprints[$data['id']] = new ReviewedPreprint($data['id'], json_encode($data));
                if (!empty($this->limit) && count($reviewed_preprints) >= $this->limit) {
                  return $reviewed_preprints;
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
    return $reviewed_preprints;
  }

}
