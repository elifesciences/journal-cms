<?php

namespace Drupal\jcms_rest\Tests\Functional;

use Drupal\Tests\UnitTestCase;
use eLife\ApiValidator\MessageValidator\FakeHttpsMessageValidator;
use eLife\ApiValidator\MessageValidator\JsonMessageValidator;
use eLife\ApiValidator\SchemaFinder\PuliSchemaFinder;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Puli\GeneratedPuliFactory;
use Webmozart\Json\JsonDecoder;
use GuzzleHttp\Client;

/**
 * @group jcms_rest
 * @todo Make this work with KernelTestBase instead of UnitTestCase.
 */
class RamlSchemaValidationTest extends UnitTestCase {

  /**
   * @var \GuzzleHttp\Client
   */
  protected $client;

  protected $projectRoot;

  protected $resourceRepository;

  protected static $contentGenerated = FALSE;

  function setUp() {
    parent::setUp();
    $this->projectRoot = realpath(__DIR__ . '/../../../../../..');
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
   * Validates JSON data against RAML schemas.
   *
   * @param \GuzzleHttp\Psr7\Response $response
   */
  protected function validateResponse(Response $response) {
    $json_decoder = new JsonDecoder();
    $messageValidator = new FakeHttpsMessageValidator(new JsonMessageValidator(new PuliSchemaFinder($this->resourceRepository), $json_decoder), $json_decoder);
    $messageValidator->validate($response);
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
        '/labs-experiments',
        'number',
        'application/vnd.elife.labs-experiment-list+json;version=1',
        'application/vnd.elife.labs-experiment+json;version=1',
      ],
    ];
  }

  /**
   * @test
   * @dataProvider dataProvider
   */
  public function testNoData(string $http_method, string $endpoint, string $id_key, string $media_type_list, string $media_type_item) {
    $this->markTestIncomplete('This test has not been implemented yet.');
    $list_response = $this->makeGuzzleRequest($http_method, $endpoint, $media_type_list);
    $this->validateResponse($list_response);
    $this->assertEquals(200, $list_response->getStatusCode());
    $item_id = $id_key == 'number' ? 1234 : 'does-not-exist';
    $item_response = $this->makeGuzzleRequest($http_method, $endpoint . '/' . $item_id, $media_type_item);
    $this->validateResponse($item_response);
    $this->assertEquals(404, $item_response->getStatusCode());
  }

  /**
   * @test
   * @*depends testNoData
   * @dataProvider dataProvider
   */
  public function testListData(string $http_method, string $endpoint, string $id_key, string $media_type_list, string $media_type_item) {
    // Generate content once.
    if (!self::$contentGenerated) {
      self::$contentGenerated = TRUE;
      $script = $this->projectRoot . '/scripts/generate_content.sh';
      $this->assertFileExists($script);
      shell_exec("$script >/dev/null 2>&1");
    }
    $list_response = $this->makeGuzzleRequest($http_method, $endpoint, $media_type_list);
    $data = json_decode($list_response->getBody()->getContents());
    $this->validateResponse($list_response);
    $this->assertEquals(200, $list_response->getStatusCode());
    // To be added when generating content for other items than subjects.
    //$this->assertNotEmpty($data->items);
    foreach ($data->items as $item) {
      $item_response = $this->makeGuzzleRequest($http_method, $endpoint . '/' . $item->{$id_key}, $media_type_item);
      $this->validateResponse($item_response);
      $this->assertEquals(200, $item_response->getStatusCode());
    }
  }

}

