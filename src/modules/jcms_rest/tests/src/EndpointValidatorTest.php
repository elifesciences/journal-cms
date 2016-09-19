<?php
namespace Drupal\Tests\jcms_rest\Unit;

use Drupal\Tests\UnitTestCase;
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
    $this->client = new Client([
      'base_uri' => 'http://127.0.0.1:8080/',
      'http_errors' => FALSE,
    ]);
    // Setup Puli.
    $this->resourceRepository = (new GeneratedPuliFactory)->createRepository();
  }

  public function tearDown() {
  }

  /**
   * @test
   * @expectedException \eLife\ApiValidator\Exception\SchemaNotFound
   */
  public function testSubjectsEndpoint() {
    $this->markTestIncomplete(
      'This test has not been implemented yet.'
    );
    $request = new Request('GET', '/subjects', [
      'Accept' => 'application/vnd.elife.annual-report-list+json;version=1',
    ]);
    $response = $this->client->send($request);
    $messageValidator = new JsonMessageValidator(new PuliSchemaFinder($this->resourceRepository), new JsonDecoder());
    $messageValidator->validate($response);
  }

}
