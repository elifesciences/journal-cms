<?php

namespace Drupal\Tests\jcms_rest\Functional;

use GuzzleHttp\Psr7\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test to interrogate deprecation items in a query to a list API endpoint.
 *
 * @package Drupal\Tests\jcms_rest\Functional
 */
class DeprecationEndpointValidatorTest extends FixtureBasedTestCase {

  /**
   * Provider for deprecated lists.
   */
  public function dataListProvider() : array {
    return [
      [
        '/annual-reports',
        'application/vnd.elife.annual-report-list+json;version=1',
        '299 api.elifesciences.org "Deprecation: Support for version 1 will be removed"',
      ],
      [
        '/highlights/magazine',
        'application/vnd.elife.highlight-list+json;version=1',
        '299 api.elifesciences.org "Deprecation: Support for version 1 will be removed"',
      ],
      [
        '/highlights/magazine',
        'application/vnd.elife.highlight-list+json;version=2',
        '299 api.elifesciences.org "Deprecation: Support for version 2 will be removed"',
      ],
      [
        '/highlights/community',
        'application/vnd.elife.highlight-list+json;version=1',
        '299 api.elifesciences.org "Deprecation: Support for version 1 will be removed"',
      ],
      [
        '/highlights/community',
        'application/vnd.elife.highlight-list+json;version=2',
        '299 api.elifesciences.org "Deprecation: Support for version 2 will be removed"',
      ],
      [
        '/highlights/announcements',
        'application/vnd.elife.highlight-list+json;version=1',
        '299 api.elifesciences.org "Deprecation: Support for version 1 will be removed"',
      ],
      [
        '/highlights/announcements',
        'application/vnd.elife.highlight-list+json;version=2',
        '299 api.elifesciences.org "Deprecation: Support for version 2 will be removed"',
      ],
    ];
  }

  /**
   * Test deprecated list endpoints.
   *
   * @test
   * @dataProvider dataListProvider
   */
  public function testDeprecationListEndpoints(string $endpoint, string $media_type_list, $check = []) {
    $per_page = 10;
    $page = 1;
    if (is_string($check)) {
      $check = ['Warning' => $check];
    }
    do {
      $request = new Request('GET', $endpoint . '?per-page=' . $per_page . '&page=' . $page, [
        'Accept' => $media_type_list,
      ]);

      $response = $this->client->send($request);
      $this->assertContains($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_NOT_ACCEPTABLE]);
      if ($response->getStatusCode() == Response::HTTP_OK) {
        foreach ($check as $header => $value) {
          $this->assertEquals($response->getHeaderLine($header), $value);
        }
      }

      $data = \GuzzleHttp\json_decode((string) $response->getBody());
      $items = isset($data->items) ? $data->items : $data;

      if (count($items) < $per_page) {
        $page = -1;
      }
      else {
        $page++;
      }
    } while ($page > 0);
  }

  /**
   * {@inheritdoc}
   */
  public function dataProvider() : array {
    return [
      [
        '/annual-reports',
        'year',
        'application/vnd.elife.annual-report-list+json',
        'application/vnd.elife.annual-report+json;version=1',
        '299 api.elifesciences.org "Deprecation: Support for version 1 will be removed"',
      ],
      [
        '/blog-articles',
        'id',
        'application/vnd.elife.blog-article-list+json',
        'application/vnd.elife.blog-article+json;version=1',
        '299 api.elifesciences.org "Deprecation: Support for version 1 will be removed"',
      ],
      [
        '/collections',
        'id',
        'application/vnd.elife.collection-list+json',
        'application/vnd.elife.collection+json;version=1',
        '299 api.elifesciences.org "Deprecation: Support for version 1 will be removed"',
      ],
      [
        '/events',
        'id',
        'application/vnd.elife.event-list+json',
        'application/vnd.elife.event+json;version=1',
        '299 api.elifesciences.org "Deprecation: Support for version 1 will be removed"',
      ],
      [
        '/interviews',
        'id',
        'application/vnd.elife.interview-list+json',
        'application/vnd.elife.interview+json;version=1',
        '299 api.elifesciences.org "Deprecation: Support for version 1 will be removed"',
      ],
      [
        '/labs-posts',
        'id',
        'application/vnd.elife.labs-post-list+json',
        'application/vnd.elife.labs-post+json;version=1',
        '299 api.elifesciences.org "Deprecation: Support for version 1 will be removed"',
      ],
      [
        '/press-packages',
        'id',
        'application/vnd.elife.press-package-list+json',
        'application/vnd.elife.press-package+json;version=1',
        '299 api.elifesciences.org "Deprecation: Support for version 1 will be removed"',
      ],
      [
        '/press-packages',
        'id',
        'application/vnd.elife.press-package-list+json',
        'application/vnd.elife.press-package+json;version=2',
        '299 api.elifesciences.org "Deprecation: Support for version 2 will be removed"',
      ],
    ];
  }

  /**
   * Test deprecated endpoints recursively.
   *
   * @test
   * @dataProvider dataProvider
   * {@inheritdoc}
   */
  public function testDeprecationEndpointsRecursively(string $endpoint, string $id_key, string $media_type_list, $media_type_item = NULL, $check = []) {
    $items = $this->gatherListItems($endpoint, $media_type_list);
    if (is_string($check)) {
      $check = ['Warning' => $check];
    }

    foreach ($items as $item) {
      $request = new Request('GET', $endpoint . '/' . $item->{$id_key}, [
        'Accept' => $media_type_item,
      ]);

      $response = $this->client->send($request);
      $this->assertContains($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_NOT_ACCEPTABLE]);
      if ($response->getStatusCode() == Response::HTTP_OK) {
        foreach ($check as $header => $value) {
          $this->assertEquals($response->getHeaderLine($header), $value);
        }
        $this->validator->validate($response);
      }
    }
  }

}
