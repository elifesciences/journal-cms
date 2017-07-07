<?php

namespace Drupal\Tests\jcms_rest\Functional;

use GuzzleHttp\Psr7\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test to interrogate deprecation items in a query to a list API endpoint.
 *
 * @package Drupal\Tests\jcms_rest\Functional
 */
class DeprecationEndpointValidatorTest extends RecursiveEndpointValidatorTest {

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
        '299 api.elifesciences.org "Deprecation: Support for version 1 will be removed"',
      ],
    ];
  }

  /**
   * @test
   * @dataProvider dataProvider
   * {@inheritdoc}
   */
  public function testValidEndpointsRecursively(string $endpoint, string $id_key, string $media_type_list, $media_type_item = NULL, $check = []) {
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
