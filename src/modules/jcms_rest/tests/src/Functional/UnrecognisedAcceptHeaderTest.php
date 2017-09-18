<?php

namespace Drupal\Tests\jcms_rest\Functional;

use Drupal\Tests\UnitTestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test to verify an appropriate response when unrecognised accept headers provided.
 *
 * @package Drupal\Tests\jcms_rest\Functional
 */
class UnrecognisedAcceptHeaderTest extends UnitTestCase {

  /**
   * @var Client
   */
  protected $client;

  public function setUp() {
    $this->client = new Client([
      'base_uri' => 'http://journal-cms.local/',
      'http_errors' => FALSE,
    ]);
  }

  /**
   * Data provider for the validator test.
   *
   * @return array
   */
  public function dataProvider() : array {
    return [
      [
        '/subjects',
        [
          'application/vnd.elife.subject-list+json;version=1',
          'application/vnd.elife.subject+json;version=1',
        ],
        'id',
      ],
      [
        '/blog-articles',
        [
          'application/vnd.elife.blog-article-list+json;version=1',
          'application/vnd.elife.blog-article+json;version=1',
        ],
        'id',
      ],
      [
        '/labs-posts',
        [
          'application/vnd.elife.labs-post-list+json;version=1',
          'application/vnd.elife.labs-post+json;version=1',
        ],
        'id',
      ],
      /**
       * 500 error when content is in there
      [
        '/people',
        [
          'application/vnd.elife.person-list+json;version=1',
          'application/vnd.elife.person+json;version=1',
        ],
        'id',
      ],
       */
      [
        '/podcast-episodes',
        [
          'application/vnd.elife.podcast-episode-list+json;version=1',
          'application/vnd.elife.podcast-episode+json;version=1',
        ],
        'number',
      ],
      [
        '/interviews',
        [
          'application/vnd.elife.interview-list+json;version=1',
          'application/vnd.elife.interview+json;version=1',
        ],
        'id',
      ],
      [
        '/annual-reports',
        [
          'application/vnd.elife.annual-report-list+json;version=1',
          'application/vnd.elife.annual-report+json;version=1',
        ],
        'year',
      ],
      [
        '/events',
        [
          'application/vnd.elife.event-list+json;version=1',
          'application/vnd.elife.event+json;version=1',
        ],
        'id',
      ],
      [
        '/collections',
        [
          'application/vnd.elife.collection-list+json;version=1',
          'application/vnd.elife.collection+json;version=1',
        ],
        'id',
      ],
      [
        '/press-packages',
        [
          'application/vnd.elife.press-package-list+json;version=1',
          'application/vnd.elife.press-package+json;version=2',
        ],
        'id',
      ],
      [
        '/community',
        [
          'application/vnd.elife.community-list+json;version=1',
        ],
      ],
      [
        '/covers',
        [
          'application/vnd.elife.cover-list+json;version=1',
        ],
      ],
      [
        '/covers/current',
        [
          'application/vnd.elife.cover-list+json;version=1',
        ],
      ],
    ];
  }

  /**
   * @test
   * @dataProvider dataProvider
   * @param string $endpoint
   * @param array|string $expected_content_type
   * @param string|NULL $id_key
   */
  public function testResponses(string $endpoint, $expected_content_type, $id_key = NULL) {
    foreach ([[], ['Accept' => '*/*'], ['Accept' => 'foo']] as $headers) {
      $request = new Request('GET', $endpoint, $headers);
      $response = $this->client->send($request);
      $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
      $expected_content_type = (array) $expected_content_type;
      if (isset($expected_content_type[0])) {
        $this->assertEquals($expected_content_type[0], $response->getHeaderLine('Content-Type'));
      }
      if (!is_null($id_key)) {
        $data = \GuzzleHttp\json_decode((string) $response->getBody());
        if (!empty($data->items)) {
          $item = reset($data->items);
          $request = new Request('GET', $endpoint . '/' . $item->{$id_key}, $headers);
          $response = $this->client->send($request);
          $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
          if (isset($expected_content_type[1])) {
            $this->assertEquals($expected_content_type[1], $response->getHeaderLine('Content-Type'));
          }
        }
      }
    }
  }
}
