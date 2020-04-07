<?php

namespace Drupal\Tests\jcms_rest\Functional;

use GuzzleHttp\Psr7\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test correct response for requests with query parameters on API endpoints.
 *
 * @package Drupal\Tests\jcms_rest\Functional
 */
class ValidQueryEndpointTest extends FixtureBasedTestCase {

  /**
   * Provider for valid query parameters.
   */
  public function validQueryEndpointProvider() : array {
    return [
      'containing - collections' => [
        '/collections?containing[]=article/1&containing[]=blog-article/2&containing[]=digest/3&containing[]=event/4&containing[]=interview/5',
        200,
      ],
      'containing - collections - unsupported' => [
        '/collections?containing[]=article/1&containing[]=collection/2',
        400,
        'Invalid containing parameter',
      ],
      'containing - promotional-collections' => [
        '/promotional-collections?containing[]=article/1&containing[]=blog-article/2&containing[]=digest/3&containing[]=event/4&containing[]=interview/5',
        200,
      ],
      'containing - promotional-collections - unsupported' => [
        '/promotional-collections?containing[]=article/1&containing[]=promotional-collection/2',
        400,
        'Invalid containing parameter',
      ],
      'containing - podcast-episodes' => [
        '/podcast-episodes?containing[]=article/1&containing[]=collection/2',
        200,
      ],
      'containing - podcast-episodes - unsupported' => [
        '/podcast-episodes?containing[]=article/1&containing[]=blog-article/2',
        400,
        'Invalid containing parameter',
      ],
      'order - desc' => [
        '/collections?order=desc',
        200,
      ],
      'order - asc' => [
        '/collections?order=asc',
        200,
      ],
      'order - unsupported' => [
        '/collections?order=unsupported',
        400,
        'Invalid order option',
      ],
      'start-date' => [
        '/events?start-date=2020-01-01',
        200,
      ],
      'start-date - unsupported' => [
        '/covers?start-date=unsupported',
        400,
        'Invalid start date',
      ],
      'end-date' => [
        '/events?end-date=2020-01-02',
        200,
      ],
      'end-date - unsupported' => [
        '/covers?end-date=2020/1/1',
        400,
        'Invalid end date',
      ],
      'start-date before end-date' => [
        '/events?start-date=2020-01-01&end-date=2020-01-02',
        200,
      ],
      'start-date same as end-date' => [
        '/covers?start-date=2020-01-01&end-date=2020-01-01',
        200,
      ],
      'start-date after end-date' => [
        '/covers?start-date=2020-01-02&end-date=2020-01-01',
        400,
        'End date must be on or after start date',
      ],
      'use-date - published' => [
        '/covers?use-date=published',
        200,
      ],
      'use-date - default' => [
        '/covers?use-date=default',
        200,
      ],
      'use-date - unsupported' => [
        '/covers?use-date=unsupported',
        400,
        'Invalid use date',
      ],
      'sort - date' => [
        '/events?sort=date',
        200,
      ],
      'sort - page-views' => [
        '/events?sort=page-views',
        200,
      ],
      'sort - unsupported' => [
        '/events?sort=unsupported',
        400,
        'Invalid sort option',
      ],
      'show - all' => [
        '/events?show=all',
        200,
      ],
      'show - unsupported' => [
        '/events?show=unsupported',
        400,
        'Invalid show option',
      ],
    ];
  }

  /**
   * Test valid query parameters requests.
   *
   * @test
   * @dataProvider validQueryEndpointProvider
   */
  public function validQueryEndpoint(string $url, int $expected_status, string $message = NULL) {
    $request = new Request('GET', $url);
    $response = $this->client->send($request);
    $this->assertEquals($expected_status, $response->getStatusCode());
    if (Response::HTTP_BAD_REQUEST === $expected_status) {
      $this->assertEquals('application/problem+json', $response->getHeaderLine('Content-Type'));
      $data = \GuzzleHttp\json_decode((string) $response->getBody());
      $this->assertEquals($message, $data->message);
    }
  }

}
