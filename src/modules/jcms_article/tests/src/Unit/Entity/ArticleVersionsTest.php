<?php

namespace Drupal\jcms_article\Tests\Unit\Entity;

use Drupal\Tests\UnitTestCase;
use Drupal\jcms_article\Entity\ArticleVersions;

/**
 * Class ArticleVersionsTest
 *
 * @package Drupal\jcms_article\Tests\Unit\Entity
 */
class ArticleVersionsTest extends UnitTestCase {

  protected $testJson;

  public function setUp() {
    parent::setUp();
    // This is cut down but follows the correct version JSON structure.
    $this->testJson = '{"versions":[{"stage":"published","title":"Older published"},{"stage":"preview","title":"Older preview"},{"stage":"published","title":"Latest published"},{"stage":"preview","title":"Latest preview"}]}';
  }

  /**
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
   * @test
   * @group journal-cms-tests
   */
  public function testGetLatestStageVersionJsonNoVersions() {
    $versions = new ArticleVersions(19887, '{}');
    $actual = $versions->getLatestUnpublishedVersionJson();
    $this->assertEmpty($actual);
  }

}
