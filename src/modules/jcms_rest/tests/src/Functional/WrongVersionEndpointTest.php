<?php

namespace Drupal\Tests\jcms_rest\Functional;

use GuzzleHttp\Psr7\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test correct response for wrong version on API endpoints.
 *
 * @package Drupal\Tests\jcms_rest\Functional
 */
class WrongVersionEndpointTest extends FixtureBasedTestCase {

  /**
   * Provider for wrong version list requests.
   */
  public function wrongVersionListProvider() : array {
    return [
      [
        '/annual-reports',
        [
          'application/vnd.elife.annual-report-list+json; version=1',
          'application/vnd.elife.annual-report-list+json; version=3',
        ],
      ],
      [
        '/blog-articles',
        [
          'application/vnd.elife.blog-article-list+json; version=2',
        ],
      ],
      [
        '/collections',
        [
          'application/vnd.elife.collection-list+json; version=2',
        ],
      ],
      [
        '/covers',
        [
          'application/vnd.elife.cover-list+json; version=2',
        ],
      ],
      [
        '/events',
        [
          'application/vnd.elife.event-list+json; version=2',
        ],
      ],
      [
        '/job-adverts',
        [
          'application/vnd.elife.job-advert-list+json; version=2',
        ],
      ],
      [
        '/labs-posts',
        [
          'application/vnd.elife.labs-post-list+json; version=2',
        ],
      ],
      [
        '/people',
        [
          'application/vnd.elife.person-list+json; version=2',
        ],
      ],
      [
        '/podcast-episodes',
        [
          'application/vnd.elife.podcast-episode-list+json; version=2',
        ],
      ],
      [
        '/press-packages',
        [
          'application/vnd.elife.press-package-list+json; version=2',
        ],
      ],
      [
        '/promotional-collections',
        [
          'application/vnd.elife.promotional-collection-list+json; version=2',
        ],
      ],
      [
        '/subjects',
        [
          'application/vnd.elife.subject-list+json; version=2',
        ],
      ],
    ];
  }

  /**
   * Test wrong version list requests.
   *
   * @test
   * @dataProvider wrongVersionListProvider
   */
  public function testWrongVersionList(string $endpoint, array $media_types) {
    foreach ($media_types as $media_type) {
      $request = new Request('GET', $endpoint, [
        'Accept' => $media_type,
      ]);

      $response = $this->client->send($request);
      $this->assertEquals(Response::HTTP_NOT_ACCEPTABLE, $response->getStatusCode());
      $this->assertEquals('application/problem+json', $response->getHeaderLine('Content-Type'));
      $data = \GuzzleHttp\json_decode((string) $response->getBody());
      $this->assertEquals($media_type . ' is not supported', $data->title);
    }
  }

  /**
   * Provider for wrong version item requests.
   */
  public function wrongVersionItemProvider() : array {
    return [
      [
        '/annual-reports',
        'year',
        [
          'application/vnd.elife.annual-report+json; version=1',
          'application/vnd.elife.annual-report+json; version=3',
        ],
      ],
      [
        '/blog-articles',
        'id',
        [
          'application/vnd.elife.blog-article+json; version=3',
        ],
      ],
      [
        '/collections',
        'id',
        [
          'application/vnd.elife.collection+json; version=3',
        ],
      ],
      [
        '/events',
        'id',
        [
          'application/vnd.elife.event+json; version=3',
        ],
      ],
      [
        '/labs-posts',
        'id',
        [
          'application/vnd.elife.labs-post+json; version=3',
        ],
      ],
      [
        '/people',
        'id',
        [
          'application/vnd.elife.person+json; version=2',
        ],
      ],
      [
        '/promotional-collections',
        'id',
        [
          'application/vnd.elife.promotional-collection+json; version=2',
        ],
      ],
      [
        '/subjects',
        'id',
        [
          'application/vnd.elife.subject+json; version=2',
        ],
      ],
    ];
  }

  /**
   * Test wrong version item requests.
   *
   * @test
   * @dataProvider wrongVersionItemProvider
   */
  public function testWrongVersionItem(string $endpoint, string $id_key, array $media_types) {
    $request = new Request('GET', $endpoint . '?per-page=1');
    $response = $this->client->send($request);
    $data = \GuzzleHttp\json_decode((string) $response->getBody());
    $this->assertGreaterThanOrEqual(1, (int) $data->total);
    $id = $data->items[0]->{$id_key};

    foreach ($media_types as $media_type) {
      $request = new Request('GET', $endpoint . '/' . $id, [
        'Accept' => $media_type,
      ]);

      $response = $this->client->send($request);
      $this->assertEquals(Response::HTTP_NOT_ACCEPTABLE, $response->getStatusCode());
      $this->assertEquals('application/problem+json', $response->getHeaderLine('Content-Type'));
      $data = \GuzzleHttp\json_decode((string) $response->getBody());
      $this->assertEquals($media_type . ' is not supported', $data->title);
    }
  }

}
