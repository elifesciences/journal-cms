<?php

namespace Drupal\Tests\jcms_rest\Functional;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;

/**
 * Tests for Etag.
 *
 * @group jcms_rest
 */
class EtagTest extends FixtureBasedTestCase {

  /**
   * Makes a Guzzle request and returns a response object.
   */
  protected function makeGuzzleRequest(string $endpoint, array $headers = [], string $method = 'GET') : Response {
    $request = new Request($method, $endpoint, $headers);
    $response = $this->client->send($request);
    return $response;
  }

  /**
   * Test Etag.
   *
   * @test
   */
  public function testEtag() {
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
