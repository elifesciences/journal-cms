<?php

namespace Drupal\jcms_article\Entity;

/**
 * Class Article
 *
 * @package Drupal\jcms_article\Entity
 */
final class Article {

  /**
   * @var string
   */
  private $id;

  /**
   * @var string
   */
  private $json = '';

  /**
   * Article constructor.
   *
   * @param string $id
   * @param string $json
   */
  public function __construct(string $id, string $json) {
    if (!$this->isValidJson($json)) {
      throw new \InvalidArgumentException('JSON error: ' . json_last_error_msg());
    }
    $this->id = $id;
    $this->json = $json;
  }

  /**
   * Checks if the string passed is valid JSON (passes with an empty string).
   *
   * @param string $json
   *
   * @return bool
   */
  public function isValidJson(string $json) {
    json_decode($json);
    return (json_last_error() === JSON_ERROR_NONE);
  }

  /**
   * Returns the article ID.
   *
   * @return string
   */
  public function getId(): string {
    return $this->id;
  }

  /**
   * Returns a string of JSON or an empty string.
   *
   * @return string
   */
  public function getJson(): string {
    return $this->json;
  }

  /**
   * Returns an object of the JSON or an empty object.
   *
   * @return \stdClass
   */
  public function getJsonObject(): \stdClass {
    return json_decode($this->getJson());
  }

}
