<?php

namespace Drupal\Tests\jcms_rest\Functional;

use GuzzleHttp\Psr7\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test to interrogate unsupported items in a query to a list API endpoint.
 *
 * @package Drupal\Tests\jcms_rest\Functional
 */
class UnsupportedEndpointValidatorTest extends FixtureBasedTestCase {

  /**
   * {@inheritdoc}
   */
  public function dataProvider() : array {
    return [
      [
        '/press-packages',
        'id',
        'application/vnd.elife.press-package-list+json',
        'application/vnd.elife.press-package+json;version=1',
        'This press package requires version 2+.',
      ],
    ];
  }

  /**
   * Test unsupported endpoints recursively.
   *
   * @test
   * @dataProvider dataProvider
   */
  public function testUnsupportedEndpointsRecursively(string $endpoint, string $id_key, string $media_type_list, $media_type_item = NULL, $check = '') {
    $items = $this->gatherListItems($endpoint, $media_type_list);

    foreach ($items as $item) {
      $request = new Request('GET', $endpoint . '/' . $item->{$id_key}, [
        'Accept' => $media_type_item,
      ]);

      $response = $this->client->send($request);
      $this->assertContains($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_NOT_ACCEPTABLE]);

      if ($response->getStatusCode() == Response::HTTP_NOT_ACCEPTABLE) {
        $body = \GuzzleHttp\json_decode((string) $response->getBody());
        $this->assertEquals($check, $body->title);
        $this->validator->validate($response);
      }
    }
  }

}
