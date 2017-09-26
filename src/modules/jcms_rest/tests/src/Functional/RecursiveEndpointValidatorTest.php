<?php

namespace Drupal\Tests\jcms_rest\Functional;

use ComposerLocator;
use eLife\ApiValidator\MessageValidator;
use eLife\ApiValidator\MessageValidator\FakeHttpsMessageValidator;
use eLife\ApiValidator\SchemaFinder\PathBasedSchemaFinder;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use eLife\ApiValidator\MessageValidator\JsonMessageValidator;
use JsonSchema\Validator;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test to interrogate all items in a query to a list API endpoint.
 *
 * This is useful to verify that the migration of content has been successful.
 *
 * @package Drupal\Tests\jcms_rest\Functional
 */
class RecursiveEndpointValidatorTest extends FixtureBasedTestCase {

  /**
   * @var Client
   */
  protected $client;

  /**
   * @var MessageValidator
   */
  protected $validator;

  public function setUp() {
    $this->validator = new FakeHttpsMessageValidator(
      new JsonMessageValidator(
        new PathBasedSchemaFinder(ComposerLocator::getPath('elife/api').'/dist/model'),
        new Validator()
      )
    );
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
      [
        '/subjects',
        'id',
        'application/vnd.elife.subject-list+json',
        'application/vnd.elife.subject+json',
      ],
      /*
      [
        '/blog-articles',
        'id',
        'application/vnd.elife.blog-article-list+json',
        'application/vnd.elife.blog-article+json',
      ],
       */
      [
        '/labs-posts',
        'id',
        'application/vnd.elife.labs-post-list+json',
        'application/vnd.elife.labs-post+json',
      ],
      /*
      [
        '/people',
        'id',
        'application/vnd.elife.person-list+json',
        'application/vnd.elife.person+json',
      ],
      [
        '/podcast-episodes',
        'number',
        'application/vnd.elife.podcast-episode-list+json',
        'application/vnd.elife.podcast-episode+json',
      ],
       */
      [
        '/interviews',
        'id',
        'application/vnd.elife.interview-list+json',
        'application/vnd.elife.interview+json',
      ],
      [
        '/annual-reports',
        'year',
        'application/vnd.elife.annual-report-list+json',
        'application/vnd.elife.annual-report+json',
      ],
      [
        '/events',
        'id',
        'application/vnd.elife.event-list+json',
        'application/vnd.elife.event+json',
      ],
      /*
      [
        '/collections',
        'id',
        'application/vnd.elife.collection-list+json',
        'application/vnd.elife.collection+json',
      ],
      [
        '/press-packages',
        'id',
        'application/vnd.elife.press-package-list+json',
        'application/vnd.elife.press-package+json',
      ],
      [
        '/community',
        'type',
        'application/vnd.elife.community-list+json',
      ],
       */
      [
        '/covers',
        'type',
        'application/vnd.elife.cover-list+json',
      ],
      [
        '/covers/current',
        'type',
        'application/vnd.elife.cover-list+json',
      ],
    ];
  }

  /**
   * @test
   * @dataProvider dataProvider
   * @param string $endpoint
   * @param string $id_key
   * @param string $media_type_list
   * @param string|NULL $media_type_item
   * @param array|string $check
   */
  public function testValidEndpointsRecursively(string $endpoint, string $id_key, string $media_type_list, $media_type_item = NULL, $check = []) {
    $items = $this->gatherListItems($endpoint, $media_type_list);

    foreach ($items as $item) {
      if (isset($item->item)) {
        $item = $item->item;
      }

      if ($id_key != 'type') {
        $request = new Request('GET', $endpoint . '/' . $item->{$id_key}, [
          'Accept' => $media_type_item,
        ]);
      }
      elseif (isset($item->{$id_key}) && in_array($item->{$id_key}, ['blog-article', 'collection', 'event', 'interview', 'labs-experiment', 'podcast-episode'])) {
        switch ($item->{$id_key}) {
          case 'podcast-episode':
            $id = $item->number;
            break;
          default:
            $id = $item->id;
        }

        $request = new Request('GET', $item->{$id_key} . 's/' . $id, [
          'Accept' => 'application/vnd.elife.' . $item->{$id_key} . '+json',
        ]);
      }
      else {
        continue;
      }

      $response = $this->client->send($request);
      $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
      $this->assertFalse($response->hasHeader('X-Generator'), 'Did not set the X-Generator header.');
      if (is_array($check)) {
        foreach ($check as $header => $value) {
          $this->assertEquals($response->getHeaderLine($header), $value);
        }
      }
      $this->validator->validate($response);
    }
  }

  public function gatherListItems(string $endpoint, string $media_type_list) {
    $all_items = [];
    $per_page = 50;
    $page = 1;
    do {
      $request = new Request('GET', $endpoint . '?per-page=' . $per_page . '&page=' . $page, [
        'Accept' => $media_type_list,
      ]);

      $response = $this->client->send($request);
      $data = \GuzzleHttp\json_decode((string) $response->getBody());
      $this->validator->validate($response);

      $items = isset($data->items) ? $data->items : $data;
      if (!empty($items)) {
        $all_items = array_merge($all_items, $items);
      }

      if (count($items) < $per_page) {
        $page = -1;
      }
      else {
        $page++;
      }
    }
    while ($page > 0);

    return $all_items;
  }

}
