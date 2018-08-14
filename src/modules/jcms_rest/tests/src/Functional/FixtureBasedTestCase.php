<?php

namespace Drupal\Tests\jcms_rest\Functional;

use ComposerLocator;
use Drupal\Tests\UnitTestCase;
use eLife\ApiValidator\MessageValidator\FakeHttpsMessageValidator;
use eLife\ApiValidator\MessageValidator\JsonMessageValidator;
use eLife\ApiValidator\SchemaFinder\PathBasedSchemaFinder;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use JsonSchema\Validator;
use RuntimeException;

/**
 * Abstract class for fixture based test cases.
 */
abstract class FixtureBasedTestCase extends UnitTestCase {

  protected static $contentGenerated = TRUE;

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
        throw new RuntimeException("File $script does not exist");
      }
      $logFile = '/tmp/generate_content.log';
      exec("$script >$logFile 2>&1", $output, $exitCode);
      if ($exitCode != 0) {
        throw new RuntimeException("$script failed. Check log file $logFile");
      }
    }
  }

  /**
   * Gather list items.
   */
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
    } while ($page > 0);

    return $all_items;
  }

}
