<?php

namespace Drupal\jcms_article\Entity;

/**
 * Store and verify reviewed preprint snippet.
 *
 * @package Drupal\jcms_article\Entity
 */
final class ReviewedPreprint {

  const WRITE = 1;
  const DELETE = 2;

  /**
   * ReviewedPreprint ID.
   *
   * @var string
   */
  private $id;

  /**
   * ReviewedPreprint snippet.
   *
   * @var string
   */
  private $json = '';

  /**
   * Action.
   *
   * @var int
   */
  private $action;

  /**
   * ReviewedPreprint constructor.
   */
  public function __construct(string $id, string $json, int $action = self::WRITE) {
    $json = $json ?: '{}';
    if (!$this->isValidJson($json)) {
      throw new \InvalidArgumentException('JSON error: ' . json_last_error_msg());
    }
    $this->id = $id;
    $this->action = $action;
    $this->json = $json;
  }

  /**
   * Generate sample json.
   */
  public function generateSampleJson() {
    $this->json = json_encode([
      'id' => (string) $this->id,
      'doi' => '10.7554/eLife.' . $this->id,
      'title' => 'Reviewed preprint ' . $this->id,
      'stage' => 'published',
      'published' => '2018-07-05T10:21:01Z',
      'reviewedDate' => '2018-07-05T10:21:01Z',
      'versionDate' => '2018-07-05T10:21:01Z',
      'statusDate' => '2018-07-05T10:21:01Z',
      'elocationId' => 'RP' . $this->id,
    ]);
  }

  /**
   * Checks if the string passed is valid JSON (passes with an empty string).
   */
  public function isValidJson(string $json) {
    json_decode($json);
    return (json_last_error() === JSON_ERROR_NONE);
  }

  /**
   * Returns the reviewed preprint ID.
   */
  public function getId(): string {
    return $this->id;
  }

  /**
   * Returns the reviewed preprint action: write or delete.
   */
  public function getAction(): string {
    return $this->action;
  }

  /**
   * Returns a string of JSON or an empty string.
   */
  public function getJson(): string {
    return $this->json;
  }

  /**
   * Returns an object of the JSON or an empty object.
   */
  public function getJsonObject(): \stdClass {
    return json_decode($this->getJson());
  }

}
