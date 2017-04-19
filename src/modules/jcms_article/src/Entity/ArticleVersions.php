<?php

namespace Drupal\jcms_article\Entity;

/**
 * Class ArticleVersions
 *
 * @package Drupal\jcms_article\Entity
 */
final class ArticleVersions {

  const WRITE = 1;

  const DELETE = 2;

  const PUBLISHED = 'published';

  const UNPUBLISHED = 'preview';

  /**
   * @var string
   */
  private $id;

  /**
   * @var string
   */
  private $json = '';

  /**
   * @var int
   */
  private $action;

  /**
   * ArticleVersions constructor.
   *
   * @param string $id
   * @param string $json
   * @param int $action
   */
  public function __construct(string $id, string $json = '', int $action = self::WRITE) {
    $json = $json ?: '{}';
    if (!$this->isValidJson($json)) {
      throw new \InvalidArgumentException('JSON error: ' . json_last_error_msg());
    }
    $this->id = $id;
    $this->action = $action;
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
   * Returns the article action, write or delete.
   *
   * @return string
   */
  public function getAction(): string {
    return $this->action;
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

  /**
   * Returns the latest published article version.
   *
   * @return string
   */
  public function getLatestPublishedVersionJson(): string {
    return $this->getLatestStageVersionJson(self::PUBLISHED);
  }

  /**
   * Returns the latest unpublished article version.
   *
   * @return string
   */
  public function getLatestUnpublishedVersionJson(): string {
    return $this->getLatestStageVersionJson(self::UNPUBLISHED);
  }

  /**
   * Returns the latest version for a stage.
   *
   * @param string $stage
   *
   * @return string
   */
  public function getLatestStageVersionJson(string $stage): string {
    $needle = '';
    $json = $this->getJsonObject();
    if (!property_exists($json, 'versions')) {
      return $needle;
    }
    $versions = array_reverse($json->versions);
    foreach ($versions as $version) {
      if ($version->stage == $stage) {
        $needle = json_encode($version);
        break;
      }
    }
    return $needle;
  }

}
