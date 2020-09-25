<?php

namespace Drupal\Tests\jcms_rest\Functional;

use ComposerLocator;
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
   * Http client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * Message validator.
   *
   * @var \eLife\ApiValidator\MessageValidator
   */
  protected $validator;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->validator = new FakeHttpsMessageValidator(
      new JsonMessageValidator(
        new PathBasedSchemaFinder(ComposerLocator::getPath('elife/api') . '/dist/model'),
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
   */
  public function dataProvider() : array {
    return [
      '/subjects' => [
        '/subjects',
        'application/vnd.elife.subject-list+json',
        'id',
        'application/vnd.elife.subject+json',
      ],
      '/blog-articles' => [
        '/blog-articles',
        'application/vnd.elife.blog-article-list+json',
        'id',
        'application/vnd.elife.blog-article+json',
      ],
      '/labs-posts' => [
        '/labs-posts',
        'application/vnd.elife.labs-post-list+json',
        'id',
        'application/vnd.elife.labs-post+json',
      ],
      '/people' => [
        '/people',
        'application/vnd.elife.person-list+json',
        'id',
        'application/vnd.elife.person+json',
      ],
      '/podcast-episodes' => [
        '/podcast-episodes',
        'application/vnd.elife.podcast-episode-list+json',
        'number',
        'application/vnd.elife.podcast-episode+json',
      ],
      '/interviews' => [
        '/interviews',
        'application/vnd.elife.interview-list+json',
        'id',
        'application/vnd.elife.interview+json',
      ],
      '/annual-reports' => [
        '/annual-reports',
        'application/vnd.elife.annual-report-list+json',
        'year',
        'application/vnd.elife.annual-report+json',
      ],
      '/events' => [
        '/events',
        'application/vnd.elife.event-list+json',
        'id',
        'application/vnd.elife.event+json',
      ],
      '/collections' => [
        '/collections',
        'application/vnd.elife.collection-list+json',
        'id',
        'application/vnd.elife.collection+json',
      ],
      '/press-packages' => [
        '/press-packages',
        'application/vnd.elife.press-package-list+json',
        'id',
        'application/vnd.elife.press-package+json',
      ],
      '/community' => [
        '/community',
        'application/vnd.elife.community-list+json',
      ],
      '/covers' => [
        '/covers',
        'application/vnd.elife.cover-list+json',
      ],
      '/covers/current' => [
        '/covers/current',
        'application/vnd.elife.cover-list+json',
      ],
      '/job-adverts' => [
        '/job-adverts',
        'application/vnd.elife.job-advert-list+json',
        'id',
        'application/vnd.elife.job-advert+json',
      ],
      '/promotional-collections' => [
        '/promotional-collections',
        'application/vnd.elife.promotional-collection-list+json',
        'id',
        'application/vnd.elife.promotional-collection+json',
      ],
    ];
  }

  /**
   * Test each endpoint recursively.
   *
   * @test
   * @dataProvider dataProvider
   */
  public function testValidEndpointsRecursively(string $endpoint, string $media_type_list, string $id_key = NULL, $media_type_item = NULL, $check = []) {
    $items = $this->gatherListItems($endpoint, $media_type_list);

    if (is_null($id_key)) {
      return;
    }

    foreach ($items as $item) {
      if (isset($item->item)) {
        $item = $item->item;
      }

      if ($id_key !== 'type' && is_string($media_type_item)) {
        $path = $endpoint . '/' . $item->{$id_key};
      }
      else {
        // Expecting blog-article, collection, event, interview,
        // labs-experiment or podcast-episode.
        switch ($item->{$id_key}) {
          case 'podcast-episode':
            $id = $item->number;
            break;

          default:
            $id = $item->id;
        }

        $path = $item->{$id_key} . 's/' . $id;
        if (is_null($media_type_item)) {
          $media_type_item = $media_type_item ?? 'application/vnd.elife.' . $item->{$id_key} . '+json';
        }
      }

      $request = new Request('GET', $path, [
        'Accept' => $media_type_item,
      ]);

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

}
