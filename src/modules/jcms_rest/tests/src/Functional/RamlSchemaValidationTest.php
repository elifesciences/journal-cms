<?php

namespace Drupal\Tests\jcms_rest\Functional;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

/**
 * Tests endpoints ot ensure that they match schema.
 *
 * @group jcms_rest
 * @todo Make this work with KernelTestBase instead of UnitTestCase.
 */
class RamlSchemaValidationTest extends FixtureBasedTestCase {

  /**
   * Makes a Guzzle request and returns a response object.
   */
  protected function makeGuzzleRequest(string $method, string $endpoint, string $media_type) : Response {
    $request = new Request($method, $endpoint, [
      'Accept' => $media_type,
    ]);
    $response = $this->client->send($request);
    return $response;
  }

  /**
   * Data provider for the validator test.
   */
  public function dataProvider() : array {
    return [
      [
        'GET',
        '/subjects',
        'id',
        'application/vnd.elife.subject-list+json;version=1',
        'application/vnd.elife.subject+json;version=1',
      ],
      [
        'GET',
        '/blog-articles',
        'id',
        'application/vnd.elife.blog-article-list+json;version=1',
        'application/vnd.elife.blog-article+json;version=1',
      ],
      [
        'GET',
        '/labs-posts',
        'id',
        'application/vnd.elife.labs-post-list+json;version=1',
        'application/vnd.elife.labs-post+json;version=1',
      ],
      [
        'GET',
        '/people',
        'id',
        'application/vnd.elife.person-list+json;version=1',
        'application/vnd.elife.person+json;version=1',
      ],
      [
        'GET',
        '/events',
        'id',
        'application/vnd.elife.event-list+json;version=1',
        'application/vnd.elife.event+json;version=1',
      ],
      [
        'GET',
        '/podcast-episodes',
        'number',
        'application/vnd.elife.podcast-episode-list+json;version=1',
        'application/vnd.elife.podcast-episode+json;version=1',
      ],
      [
        'GET',
        '/interviews',
        'id',
        'application/vnd.elife.interview-list+json;version=1',
        'application/vnd.elife.interview+json;version=1',
      ],
      'job-adverts' => [
        'GET',
        '/job-adverts',
        'id',
        'application/vnd.elife.job-advert-list+json;version=1',
        'application/vnd.elife.job-advert+json;version=1',
      ],
      [
        'GET',
        '/collections',
        'id',
        'application/vnd.elife.collection-list+json;version=1',
        'application/vnd.elife.collection+json;version=2',
      ],
      [
        'GET',
        '/press-packages',
        'id',
        'application/vnd.elife.press-package-list+json;version=1',
        'application/vnd.elife.press-package+json;version=4',
      ],
      [
        'GET',
        '/promotional-collections',
        'id',
        'application/vnd.elife.promotional-collection-list+json;version=1',
        'application/vnd.elife.promotional-collection+json;version=1',
      ],
      [
        'GET',
        '/annual-reports',
        'year',
        'application/vnd.elife.annual-report-list+json;version=2',
        'application/vnd.elife.annual-report+json;version=2',
      ],
    ];
  }

  /**
   * Test appropriate response when content not found.
   *
   * @test
   * @dataProvider dataProvider
   */
  public function testNoData(string $http_method, string $endpoint, string $id_key, string $media_type_list, string $media_type_item) {
    $list_response = $this->makeGuzzleRequest($http_method, $endpoint, $media_type_list);
    $this->validator->validate($list_response);
    $this->assertEquals(200, $list_response->getStatusCode());
    $item_id = in_array($id_key, ['number', 'year']) ? 2134 : 'does-not-exist';
    $item_response = $this->makeGuzzleRequest($http_method, $endpoint . '/' . $item_id, $media_type_item);
    $this->validator->validate($item_response);
    $this->assertEquals(404, $item_response->getStatusCode());
  }

  /**
   * Test each endpoint recursively and check response is valid.
   *
   * @test
   * @*depends testNoData
   * @dataProvider dataProvider
   */
  public function testListData(string $http_method, string $endpoint, string $id_key, string $media_type_list, string $media_type_item) {
    $list_response = $this->makeGuzzleRequest($http_method, $endpoint, $media_type_list);
    $data = json_decode((string) $list_response->getBody());
    $this->validator->validate($list_response);
    $this->assertEquals(200, $list_response->getStatusCode());
    // @todo check to ensure that $data->items is not empty.
    // To be added when generating content for all items in the data provider.
    foreach ($data->items as $item) {
      $item_response = $this->makeGuzzleRequest($http_method, $endpoint . '/' . $item->{$id_key}, $media_type_item);
      $this->validator->validate($item_response);
      $this->assertEquals(200, $item_response->getStatusCode());
    }
  }

}
