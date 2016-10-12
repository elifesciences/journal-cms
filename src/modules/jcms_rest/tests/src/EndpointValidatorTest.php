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
use Puli\Repository\Api\ResourceRepository;

/**
 * Class SubjectsRestResourceTest
 *
 * @package Drupal\Tests\jcms_rest\Unit
 * @todo    Figure out an elegant way to fix the Puli issue described below.
 * @todo    Make this a data provider for an array of endpoints.
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
      // @todo - elife - nlisgo - We should be able to make this request contextually.
      'base_uri' => 'http://127.0.0.1:8080/',
      'http_errors' => FALSE,
    ]);
  }

  public function tearDown() {
  }

  /**
   * @test
   */
  public function testSubjectsItemEndpoint() {
    $request = new Request('GET', '/subjects/plant-biology', [
      'Accept' => 'application/vnd.elife.subject+json;version=1',
    ]);
    $response = $this->client->send($request);
    $json_decoder = new JsonDecoder();
    $messageValidator = new FakeHttpsMessageValidator(new JsonMessageValidator(new PuliSchemaFinder($this->resourceRepository), $json_decoder), $json_decoder);
    $messageValidator->validate($response);
  }

}
