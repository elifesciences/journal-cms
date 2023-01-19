<?php

namespace Drupal\Tests\jcms_rest\Functional;

use Drupal\Tests\UnitTestCase;
use eLife\ApiValidator\MessageValidator\FakeHttpsMessageValidator;
use eLife\ApiValidator\MessageValidator\JsonMessageValidator;
use eLife\ApiValidator\SchemaFinder\PathBasedSchemaFinder;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use JsonSchema\Validator;

/**
 * Abstract class for fixture based test cases.
 */
abstract class FixtureBasedTestCase extends UnitTestCase {

  /**
   * Content generated flag.
   *
   * @var bool
   */
  protected static $contentGenerated = FALSE;

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
    $this->validator = new FakeHttpsMessageValidator(
      new JsonMessageValidator(
        new PathBasedSchemaFinder(\ComposerLocator::getPath('elife/api') . '/dist/model'),
        new Validator()
      )
    );
    $this->client = new Client([
      'base_uri' => 'http://journal-cms.local/',
      'http_errors' => FALSE,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public static function setUpBeforeClass() {
    parent::setUpBeforeClass();
    // Generate content once.
    if (!self::$contentGenerated) {
      self::$contentGenerated = TRUE;
      $projectRoot = realpath(__DIR__ . '/../../../../../..');
      $script = $projectRoot . '/scripts/generate_content.sh';
      if (!file_exists($script)) {
        throw new \RuntimeException("File $script does not exist");
      }
      $logFile = '/tmp/generate_content.log';
      exec("$script >$logFile 2>&1", $output, $exitCode);
      if ($exitCode != 0) {
        throw new \RuntimeException("$script failed. Check log file $logFile");
      }
    }
  }

  /**
   * Gather list items.
   */
  public function gatherListItems(string $endpoint, string $media_type_list, string $additional_parameters = '') {
    $all_items = [];
    $per_page = 50;
    $page = 1;
    $total = NULL;
    do {
      $request = new Request('GET', $endpoint . '?per-page=' . $per_page . '&page=' . $page . $additional_parameters, [
        'Accept' => $media_type_list,
      ]);

      $response = $this->client->send($request);
      $data = \GuzzleHttp\json_decode((string) $response->getBody());
      $this->validator->validate($response);

      if (is_null($total) && isset($data->total)) {
        $total = $data->total;
      }

      $items = $data->items ?? $data;
      if (!empty($items)) {
        $all_items = array_merge($all_items, $items);
      }

      if (count($items) < $per_page) {
        $page = -1;
      }
      else {
        $page++;
      }
    } while ($page > 0);

    if (!is_null($total)) {
      $this->assertEquals($total, count($all_items));
    }

    return $all_items;
  }

}
