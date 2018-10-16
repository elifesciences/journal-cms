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
  const PUBLISHED = 'published';
  const UNPUBLISHED = 'preview';

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
    if (!$this->isValidJson($json)) {
      throw new \InvalidArgumentException('JSON error: ' . json_last_error_msg());
    }
    $this->id = $id;
    $this->action = $action;
    $this->json = $json;
    $this->title = $this->getJsonObject()->title;
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
