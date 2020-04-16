<?php

namespace Drupal\Tests\jcms_rest\Functional;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verify that the containing[] filter is working.
 *
 * @package Drupal\Tests\jcms_rest\Functional
 */
class VerifyContainingFilterTest extends FixtureBasedTestCase {

  /**
   * Http client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->client = new Client([
      'base_uri' => 'http://journal-cms.local/',
      'http_errors' => FALSE,
    ]);
  }

  /**
   * Test that content can be retrieved with a containing[] filter.
   *
   * @test
   */
  public function testVerifyContainingQuery() {
    $list_request = new Request('GET', '/collections?per-page=1');
    $list_response = $this->client->send($list_request);
    $this->assertEquals(Response::HTTP_OK, $list_response->getStatusCode());
    $list = \GuzzleHttp\json_decode((string) $list_response->getBody());
    $this->assertEquals(1, count($list->items));
    $item_id = $list->items[0]->id;
    $item_request = new Request('GET', 'collections/' . $item_id);
    $item_response = $this->client->send($item_request);
    $this->assertEquals(Response::HTTP_OK, $item_response->getStatusCode());
    $item_data = \GuzzleHttp\json_decode((string) $item_response->getBody());
    $this->assertNotEmpty($item_data->content);
    $containing = [$item_data->content[0]->type, $item_data->content[0]->id];
    if (!in_array($containing[0], [
      'blog-article',
      'digest',
      'event',
      'interview',
    ])) {
      $containing[0] = 'article';
    }
    $items = $this->gatherListItems('/collections', 'application/vnd.elife.collection-list+json', '&containing[]=' . implode('/', $containing));
    $this->assertNotEmpty(array_filter($items, function ($item) use ($item_id) {
      return $item->id === $item_id;
    }));
  }

}
