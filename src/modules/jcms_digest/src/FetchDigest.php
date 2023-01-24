<?php

namespace Drupal\jcms_digest;

use Drupal\Core\Site\Settings;
use Drupal\jcms_digest\Entity\Digest;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

/**
 * Fetch Digest Snippets.
 *
 * @package Drupal\jcms_digest
 */
class FetchDigest {

  const VERSION_DIGEST = 1;
  const VERSION_DIGEST_LIST = 1;

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
   * Set retrieval limit for articles.
   *
   * @param int|null $limit
   *   Limit, if set.
   */
  public function setLimit($limit) {
    $this->limit = $limit ?: NULL;
  }

  /**
   * Gets digest by ID.
   */
  public function getDigestById(string $id): Digest {
    $response = $this->requestDigest($id);
    return new Digest($id, (string) $response->getBody());
  }

  /**
   * Makes the request to get the digest.
   *
   * @throws \TypeError
   */
  public function requestDigest(string $id): ResponseInterface {
    $options = [
      'http_errors' => FALSE,
      'headers' => [
        'Accept' => 'application/vnd.elife.digest+json;version=' . self::VERSION_DIGEST,
      ],
    ];
    if ($auth = Settings::get('jcms_article_auth_unpublished')) {
      $options['headers'] += [
        'Authorization' => $auth,
      ];
    }
    $url = Settings::get('jcms_all_digests_endpoint') . '/' . $id;
    $response = $this->client->get($url, $options);
    if ($response instanceof ResponseInterface) {
      \Drupal::logger('jcms_digest')
        ->notice(
          'Digest has been requested @url with the response: @response',
          ['@url' => $url, '@response' => $response->getBody()->getContents()]
        );
      return $response;
    }
    throw new \TypeError('Network connection interrupted on request.');
  }

  /**
   * Gets the IDs for every digest.
   */
  public function getAllDigestIds() : array {
    $ids = [];
    $digests = $this->getAllDigests();
    if ($digests) {
      foreach ($digests as $digest) {
        $ids[] = $digest->getId();
      }
    }
    return array_values($ids);
  }

  /**
   * Gets every digest.
   */
  public function getAllDigests() : array {
    $digests = [];
    $endpoint = Settings::get('jcms_all_digests_endpoint');
    if ($endpoint) {
      $stop = FALSE;
      $page = 1;
      $per_page = 100;
      $options = [
        'http_errors' => FALSE,
        'headers' => [
          'Accept' => 'application/vnd.elife.digest-list+json;version=' . self::VERSION_DIGEST_LIST,
        ],
      ];
      if ($auth = Settings::get('jcms_article_auth_unpublished')) {
        $options['headers'] += [
          'Authorization' => $auth,
        ];
      }
      while (!$stop) {
        $response = $this->client->get($endpoint, $options + [
          'query' => [
            'per-page' => $per_page,
            'page' => $page,
          ],
        ]);
        if ($response instanceof ResponseInterface) {
          $json = json_decode((string) $response->getBody(), TRUE);
          if (isset($json['items']) && !empty($json['items'])) {
            foreach ($json['items'] as $data) {
              if (isset($data['id'])) {
                $digests[$data['id']] = new Digest($data['id'], json_encode($data));
                if (!empty($this->limit) && count($digests) >= $this->limit) {
                  return $digests;
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
    return $digests;
  }

}
