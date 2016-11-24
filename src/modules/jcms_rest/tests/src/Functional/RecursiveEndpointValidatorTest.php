<?php

namespace Drupal\jcms_rest\Tests\Functional;

use Drupal\Tests\UnitTestCase;
use eLife\ApiValidator\MessageValidator\FakeHttpsMessageValidator;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use eLife\ApiValidator\MessageValidator\JsonMessageValidator;
use eLife\ApiValidator\SchemaFinder\PuliSchemaFinder;
use Symfony\Component\HttpFoundation\Response;
use Webmozart\Json\JsonDecoder;
use \Puli\GeneratedPuliFactory;

/**
 * Test to interrogate all items in a query to a list API endpoint.
 *
 * This is useful to verify that the migration of content has been successful.
 *
 * @package Drupal\Tests\jcms_rest\Unit
 */
class RecursiveEndpointValidatorTest extends UnitTestCase {

  private $projectRoot;

  private $client;

  private $resourceRepository;

  public function setUp() {
    // Using Puli CLI as a Composer dependency means the class
    // "Puli\GeneratedPuliFactory" is not found by the autoloader. In this case,
    // we load it in manually.
    $this->projectRoot = realpath(__DIR__ . '/../../../../..');
    if (!class_exists('Puli\GeneratedPuliFactory')) {
      if (file_exists($this->projectRoot . '/.puli/GeneratedPuliFactory.php')) {
        require_once($this->projectRoot . '/.puli/GeneratedPuliFactory.php');
      }
    }
    // Setup Puli.
    $this->resourceRepository = (new GeneratedPuliFactory)->createRepository();
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
//      [
//        '/subjects',
//        'id',
//        'application/vnd.elife.subject-list+json;version=1',
//        'application/vnd.elife.subject+json;version=1',
//      ],
//      [
//        '/blog-articles',
//        'id',
//        'application/vnd.elife.blog-article-list+json;version=1',
//        'application/vnd.elife.blog-article+json;version=1',
//      ],
//      [
//        '/labs-experiments',
//        'number',
//        'application/vnd.elife.labs-experiment-list+json;version=1',
//        'application/vnd.elife.labs-experiment+json;version=1',
//      ],
//      [
//        '/people',
//        'id',
//        'application/vnd.elife.person-list+json;version=1',
//        'application/vnd.elife.person+json;version=1',
//      ],
//      [
//        '/podcast-episodes',
//        'number',
//        'application/vnd.elife.podcast-episode-list+json;version=1',
//        'application/vnd.elife.podcast-episode+json;version=1',
//      ],
//      [
//        '/interviews',
//        'id',
//        'application/vnd.elife.interview-list+json;version=1',
//        'application/vnd.elife.interview+json;version=1',
//      ],
//      [
//        '/annual-reports',
//        'year',
//        'application/vnd.elife.annual-report-list+json;version=1',
//        'application/vnd.elife.annual-report+json;version=1',
//      ],
//      [
//        '/events',
//        'id',
//        'application/vnd.elife.event-list+json;version=1',
//        'application/vnd.elife.event+json;version=1',
//      ],
//      [
//        '/collections',
//        'id',
//        'application/vnd.elife.collection-list+json;version=1',
//        'application/vnd.elife.collection+json;version=1',
//      ],
      [
        '/press-packages',
        'id',
        'application/vnd.elife.press-package-list+json;version=1',
        'application/vnd.elife.press-package+json;version=1',
      ],
    ];
  }

  /**
   * @test
   * @dataProvider dataProvider
   * @param string $endpoint
   * @param string $id_key
   * @param string $media_type_list
   * @param string $media_type_item
   */
  public function testValidEndpointsRecursively(string $endpoint, string $id_key, string $media_type_list, string $media_type_item) {
    $request = new Request('GET', $endpoint . '?per-page=1', [
      'Accept' => $media_type_list,
    ]);

    $response = $this->client->send($request);
    $data = \GuzzleHttp\json_decode($response->getBody()->getContents());
    $total = $data->total;

    $request = new Request('GET', $endpoint . '?per-page=' . $total, [
      'Accept' => $media_type_list,
    ]);

    $response = $this->client->send($request);
    $data = \GuzzleHttp\json_decode($response->getBody()->getContents());
    $json_decoder = new JsonDecoder();
    $messageValidator = new FakeHttpsMessageValidator(new JsonMessageValidator(new PuliSchemaFinder($this->resourceRepository), $json_decoder), $json_decoder);
    $messageValidator->validate($response);

    foreach ($data->items as $item) {
      $request = new Request('GET', $endpoint . '/' . $item->{$id_key}, [
        'Accept' => $media_type_item,
      ]);

      $response = $this->client->send($request);
      $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
      $json_decoder = new JsonDecoder();
      $messageValidator = new FakeHttpsMessageValidator(new JsonMessageValidator(new PuliSchemaFinder($this->resourceRepository), $json_decoder), $json_decoder);
      $messageValidator->validate($response);
    }
  }

}
