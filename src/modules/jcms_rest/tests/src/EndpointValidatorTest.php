<?php

namespace Drupal\Tests\jcms_rest\Unit;

use Drupal\Tests\UnitTestCase;
use eLife\ApiValidator\MessageValidator\FakeHttpsMessageValidator;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use eLife\ApiValidator\MessageValidator\JsonMessageValidator;
use eLife\ApiValidator\SchemaFinder\PuliSchemaFinder;
use Webmozart\Json\JsonDecoder;
use \Puli\GeneratedPuliFactory;

/**
 * Class SubjectsRestResourceTest
 *
 * @package Drupal\Tests\jcms_rest\Unit
 * @todo    Figure out an elegant way to fix the Puli issue described below.
 * @see     https://github.com/puli/composer-plugin/pull/46#issuecomment-239702638
 */
class EndpointValidatorTest extends UnitTestCase {

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
      [
        'GET',
        '/subjects/plant-biology',
        ' application/vnd.elife.subject+json;version=1',
      ],
      [
        'GET',
        '/subjects',
        ' application/vnd.elife.subject-list+json;version=1',
      ],
    ];
  }

  /**
   * @test
   * @dataProvider dataProvider
   * @param string $method
   *   The HTTP method/verb to be used.
   * @param string $endpoint
   *   The endpoint/URI to test.
   * @param string $mime_type
   *   The accept header mime type to send.
   */
  public function testValidateEndpointsAgainstRaml(string $method, string $endpoint, string $mime_type) {
    $request = new Request($method, $endpoint, [
      'Accept' => $mime_type,
    ]);
    $response = $this->client->send($request);
    $json_decoder = new JsonDecoder();
    $messageValidator = new FakeHttpsMessageValidator(new JsonMessageValidator(new PuliSchemaFinder($this->resourceRepository), $json_decoder), $json_decoder);
    $messageValidator->validate($response);
  }

}
