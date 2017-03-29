<?php

namespace Drupal\jcms_rest\Tests\Functional;

use ComposerLocator;
use Drupal\Driver\Exception\Exception;
use Drupal\Tests\UnitTestCase;
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
 * @package Drupal\Tests\jcms_rest\Unit
 */
class RecursiveEndpointValidatorTest extends UnitTestCase {

  private $client;

  /**
   * @var MessageValidator
   */
  private $validator;

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
        'application/vnd.elife.subject-list+json;version=1',
        'application/vnd.elife.subject+json;version=1',
      ],
      [
        '/blog-articles',
        'id',
        'application/vnd.elife.blog-article-list+json;version=1',
        'application/vnd.elife.blog-article+json;version=1',
      ],
      [
        '/labs-experiments',
        'number',
        'application/vnd.elife.labs-experiment-list+json;version=1',
        'application/vnd.elife.labs-experiment+json;version=1',
      ],
      [
        '/people',
        'id',
        'application/vnd.elife.person-list+json;version=1',
        'application/vnd.elife.person+json;version=1',
      ],
      [
        '/podcast-episodes',
        'number',
        'application/vnd.elife.podcast-episode-list+json;version=1',
        'application/vnd.elife.podcast-episode+json;version=1',
      ],
      [
        '/interviews',
        'id',
        'application/vnd.elife.interview-list+json;version=1',
        'application/vnd.elife.interview+json;version=1',
      ],
      [
        '/annual-reports',
        'year',
        'application/vnd.elife.annual-report-list+json;version=1',
        'application/vnd.elife.annual-report+json;version=1',
      ],
      [
        '/events',
        'id',
        'application/vnd.elife.event-list+json;version=1',
        'application/vnd.elife.event+json;version=1',
      ],
      [
        '/collections',
        'id',
        'application/vnd.elife.collection-list+json;version=1',
        'application/vnd.elife.collection+json;version=1',
      ],
      [
        '/press-packages',
        'id',
        'application/vnd.elife.press-package-list+json;version=1',
        'application/vnd.elife.press-package+json;version=1',
      ],
      [
        '/community',
        'type',
        'application/vnd.elife.community-list+json;version=1',
        NULL,
      ],
      [
        '/covers',
        'type',
        'application/vnd.elife.cover-list+json;version=1',
        NULL,
      ],
      [
        '/covers/current',
        'type',
        'application/vnd.elife.cover-list+json;version=1',
        NULL,
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
   */
  public function testValidEndpointsRecursively(string $endpoint, string $id_key, string $media_type_list, $media_type_item) {
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
          case 'labs-experiment':
            $id = $item->number;
            break;
          default:
            $id = $item->id;
        }

        $request = new Request('GET', $item->{$id_key} . 's/' . $id, [
          'Accept' => 'application/vnd.elife.' . $item->{$id_key} . '+json;version=1',
        ]);
      }
      else {
        continue;
      }

      $response = $this->client->send($request);
      $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
      $this->validator->validate($response);
    }
  }

  function gatherListItems(string $endpoint, string $media_type_list) {
    $all_items = [];
    $per_page = 50;
    $page = 1;
    do {
      $request = new Request('GET', $endpoint . '?per-page=' . $per_page . '&page=' . $page, [
        'Accept' => $media_type_list,
      ]);

      $response = $this->client->send($request);
      $data = \GuzzleHttp\json_decode($response->getBody()->getContents());
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
