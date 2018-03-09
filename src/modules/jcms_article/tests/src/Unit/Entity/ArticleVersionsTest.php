<?php

namespace Drupal\jcms_article\Tests\Unit\Entity;

use Drupal\Tests\UnitTestCase;
use Drupal\jcms_article\Entity\ArticleVersions;

/**
 * Tests for ArticleVersions.
 *
 * @package Drupal\jcms_article\Tests\Unit\Entity
 */
class ArticleVersionsTest extends UnitTestCase {

  protected $testJson;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    // This is cut down but follows the correct version JSON structure.
    $this->testJson = '{"versions":[{"stage":"published","title":"Older published"},{"stage":"preview","title":"Older preview"},{"stage":"published","title":"Latest published"},{"stage":"preview","title":"Latest preview"}]}';
  }

  /**
   * Test basic getters.
   *
   * @test
   * @group journal-cms-tests
   */
  public function testBasicGetters() {
    $id = 19887;
    $json = '{"versions":[{"stage":"published","title":"Article title"}]}';
    $versions = new ArticleVersions($id, $json, ArticleVersions::DELETE);
    $this->assertEquals($id, $versions->getId());
    $this->assertEquals($json, $versions->getJson());
    $this->assertEquals(ArticleVersions::DELETE, $versions->getAction());
    $this->assertInstanceOf('stdClass', $versions->getJsonObject());
  }

  /**
   * Test reaction to invalid json.
   *
   * @test
   * @group journal-cms-tests
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidJson() {
    $id = 19887;
    $json = '{"versions":[{"stage":}';
    $versions = new ArticleVersions($id, $json, ArticleVersions::DELETE);
  }

  /**
   * Test retrieval of published article snippet.
   *
   * @test
   * @group journal-cms-tests
   */
  public function testGetLatestPublishedVersionJson() {
    $versions = new ArticleVersions(19887, $this->testJson);
    $actual = $versions->getLatestPublishedVersionJson();
    $json = json_decode($actual);
    $this->assertEquals('Latest published', $json->title);
  }

  /**
   * Test retrieval of unpublished article snippet.
   *
   * @test
   * @group journal-cms-tests
   */
  public function testGetLatestUnpublishedVersionJson() {
    $versions = new ArticleVersions(19887, $this->testJson);
    $actual = $versions->getLatestUnpublishedVersionJson();
    $json = json_decode($actual);
    $this->assertEquals('Latest preview', $json->title);
  }

  /**
   * Test empty result if no article snippet available.
   *
   * @test
   * @group journal-cms-tests
   */
  public function testGetLatestStageVersionJsonNoVersions() {
    $versions = new ArticleVersions(19887, '{}');
    $actual = $versions->getLatestUnpublishedVersionJson();
    $this->assertEmpty($actual);
  }

}
