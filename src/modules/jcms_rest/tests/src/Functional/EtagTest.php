<?php

namespace Drupal\Tests\jcms_rest\Functional;

use Drupal\Tests\UnitTestCase;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;

/**
 * @group jcms_rest
 */
class EtagTest extends UnitTestCase {

  /**
   * @var \GuzzleHttp\Client
   */
  protected $client;

  protected static $contentGenerated = FALSE;

  function setUp() {
    parent::setUp();
    $this->client = new Client([
      'base_uri' => 'http://journal-cms.local/',
      'http_errors' => FALSE,
    ]);
  }

  /**
   * Makes a Guzzle request and returns a response object.
   *
   * @param string $endpoint
   * @param array $headers
   * @param string $method
   *
   * @return Response
   */
  protected function makeGuzzleRequest(string $endpoint, $headers = [], $method = 'GET') {
    $request = new Request($method, $endpoint, $headers);
    $response = $this->client->send($request);
    return $response;
  }

  /**
   * @test
   */
  public function testEtag() {
    $this->markTestIncomplete('This test has not been implemented yet.');
    $endpoint = '/subjects';
    $response = $this->makeGuzzleRequest($endpoint);
    $this->assertEquals(HttpFoundationResponse::HTTP_OK, $response->getStatusCode());
    $this->assertNotEmpty((string) $response->getBody());
    $response = $this->makeGuzzleRequest($endpoint, ['If-None-Match' => $response->getHeaderLine('Etag')]);
    $this->assertEquals(HttpFoundationResponse::HTTP_NOT_MODIFIED, $response->getStatusCode());
    $this->assertEmpty((string) $response->getBody());
    $response = $this->makeGuzzleRequest($endpoint);
    $this->assertEquals(HttpFoundationResponse::HTTP_OK, $response->getStatusCode());
    $this->assertNotEmpty((string) $response->getBody());
    $response = $this->makeGuzzleRequest($endpoint, ['If-None-Match' => '"unrecognised-etag"']);
    $this->assertEquals(HttpFoundationResponse::HTTP_OK, $response->getStatusCode());
    $this->assertNotEmpty((string) $response->getBody());
  }

}
