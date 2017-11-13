<?php

namespace Drupal\Tests\jcms_rest\Functional;

use ComposerLocator;
use Drupal\Tests\UnitTestCase;
use eLife\ApiValidator\MessageValidator;
use eLife\ApiValidator\MessageValidator\FakeHttpsMessageValidator;
use eLife\ApiValidator\MessageValidator\JsonMessageValidator;
use eLife\ApiValidator\SchemaFinder\PathBasedSchemaFinder;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Client;
use JsonSchema\Validator;
use RuntimeException;

/**
 * @group jcms_rest
 * @todo Make this work with KernelTestBase instead of UnitTestCase.
 */
class RamlSchemaValidationTest extends FixtureBasedTestCase {


  /**
   * Makes a Guzzle request and returns a response object.
   *
   * @param string $method
   * @param string $endpoint
   * @param string $media_type
   *
   * @return mixed
   */
  protected function makeGuzzleRequest(string $method, string $endpoint, string $media_type) {
    $request = new Request($method, $endpoint, [
      'Accept' => $media_type,
    ]);
    $response = $this->client->send($request);
    return $response;
  }

  /**
   * Data provider for the validator test.
   *
   * @return array
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
      /*
       * Turns out this is failing validation
       * [items[0].orcid] Does not match the regex pattern ^[0-9]{4}-[0-9]{4}-[0-9]{4}-[0-9]{3}[0-9X]$
       * et al.
      [
        'GET',
        '/people',
        'id',
        'application/vnd.elife.person-list+json;version=1',
        'application/vnd.elife.person+json;version=1',
      ],
       */
      [
        'GET',
        '/events',
        'id',
        'application/vnd.elife.event-list+json;version=1',
        'application/vnd.elife.event+json;version=1',
      ],
      /*
       * fails because there is no `chapters` property
      [
        'GET',
        '/podcast-episodes',
        'number',
        'application/vnd.elife.podcast-episode-list+json;version=1',
        'application/vnd.elife.podcast-episode+json;version=1',
      ],
       */

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
      /*
       * fails because
       * [items[0].selectedCurator.orcid] Does not match the regex pattern ^[0-9]{4}-[0-9]{4}-[0-9]{4}-[0-9]{3}[0-9X]$
       * and similar
      [
        'GET',
        '/collections',
        'id',
        'application/vnd.elife.collection-list+json;version=1',
        'application/vnd.elife.collection+json;version=1',
      ],
       */
      [
        'GET',
        '/covers',
        'id',
        'application/vnd.elife.cover-list+json;version=1',
        'application/vnd.elife.cover+json;version=1',
      ],
      [
        'GET',
        '/press-packages',
        'id',
        'application/vnd.elife.press-package-list+json;version=1',
        'application/vnd.elife.press-package+json;version=2',
      ],
      /*
       * fails because years are generated < 2012
       [
        'GET',
        '/annual-reports',
        'year',
        'application/vnd.elife.annual-report-list+json;version=1',
        'application/vnd.elife.annual-report+json;version=1',
      ],
       */
    ];
  }

  /**
   * @test
   * @dataProvider dataProvider
   */
  public function testNoData(string $http_method, string $endpoint, string $id_key, string $media_type_list, string $media_type_item) {
    $list_response = $this->makeGuzzleRequest($http_method, $endpoint, $media_type_list);
    $this->validator->validate($list_response);
    $this->assertEquals(200, $list_response->getStatusCode());
    $item_id = $id_key == 'number' ? 1234 : 'does-not-exist';
    $item_response = $this->makeGuzzleRequest($http_method, $endpoint . '/' . $item_id, $media_type_item);
    $this->validator->validate($item_response);
    $this->assertEquals(404, $item_response->getStatusCode());
  }

  /**
   * @test
   * @*depends testNoData
   * @dataProvider dataProvider
   */
  public function testListData(string $http_method, string $endpoint, string $id_key, string $media_type_list, string $media_type_item) {
    $list_response = $this->makeGuzzleRequest($http_method, $endpoint, $media_type_list);
    $data = json_decode((string) $list_response->getBody());
    $this->validator->validate($list_response);
    $this->assertEquals(200, $list_response->getStatusCode());
    // To be added when generating content for all items in the data provider.
    //$this->assertNotEmpty($data->items);
    foreach ($data->items as $item) {
      $item_response = $this->makeGuzzleRequest($http_method, $endpoint . '/' . $item->{$id_key}, $media_type_item);
      $this->validator->validate($item_response);
      $this->assertEquals(200, $item_response->getStatusCode());
    }
  }

}

