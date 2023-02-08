<?php

namespace Drupal\jcms_digest\Entity;

/**
 * Store and verify digest snippet.
 *
 * @package Drupal\jcms_digest\Entity
 */
final class Digest {

  const WRITE = 1;
  const DELETE = 2;

  /**
   * Digest ID.
   *
   * @var string
   */
  private $id;

  /**
   * Digest Title.
   *
   * @var string
   */
  private $title;

  /**
   * Digest snippet.
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
   * Digest constructor.
   */
  public function __construct(string $id, string $json, int $action = self::WRITE) {
    $json = $json ?: '{}';
    if (!$this->isValidJson($json)) {
      throw new \InvalidArgumentException('JSON error: ' . json_last_error_msg());
    }
    $this->id = $id;
    $this->action = $action;
    $this->json = $json;
    if ($json !== '{}') {
      $this->title = $this->getJsonObject()->title;
    }
  }

  /**
   * Generate sample json.
   */
  public function generateSampleJson() {
    $this->json = json_encode([
      'id' => (string) $this->id,
      'title' => 'Digest ' . $this->id,
      'stage' => 'published',
      'published' => '2018-07-05T10:21:01Z',
      'updated' => '2018-07-05T10:21:01Z',
      'image' => [
        'thumbnail' => [
          'uri' => 'https://iiif.elifesciences.org/digests/' . $this->id . '%2Fdigest-' . $this->id . '.jpg',
          'alt' => '',
          'source' => [
            'uri' => 'https://iiif.elifesciences.org/digests/' . $this->id . '%2Fdigest-' . $this->id . '.jpg/full/full/0/default.jpg',
            'filename' => 'digest-' . $this->id . '.jpg',
            'mediaType' => 'image/jpeg',
          ],
          'size' => [
            'width' => 1920,
            'height' => 1421,
          ],
        ],
      ],
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
   * Returns the digest ID.
   */
  public function getId(): string {
    return $this->id;
  }

  /**
   * Returns the digest title.
   */
  public function getTitle(): string {
    return $this->title;
  }

  /**
   * Returns the digest action: write or delete.
   */
  public function getAction(): int {
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
