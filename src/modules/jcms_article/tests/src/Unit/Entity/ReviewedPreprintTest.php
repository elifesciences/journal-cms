<?php

namespace Drupal\jcms_article\Tests\Unit\Entity;

use Drupal\jcms_article\Entity\ReviewedPreprint;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for ReviewedPreprint.
 *
 * @package Drupal\jcms_article\Tests\Unit\Entity
 */
class ReviewedPreprintTest extends UnitTestCase {

  /**
   * Test basic getters.
   *
   * @test
   * @group journal-cms-tests
   */
  public function testBasicGetters() {
    $id = 'id';
    $json = '{}';
    $reviewedPreprint = new ReviewedPreprint($id, $json, ReviewedPreprint::DELETE);
    $this->assertEquals($id, $reviewedPreprint->getId());
    $this->assertEquals($json, $reviewedPreprint->getJson());
    $this->assertEquals(ReviewedPreprint::DELETE, $reviewedPreprint->getAction());
    $this->assertInstanceOf('stdClass', $reviewedPreprint->getJsonObject());
  }

  /**
   * Test reaction to invalid json.
   *
   * @test
   * @group journal-cms-tests
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidJson() {
    $id = 'id';
    $json = '{';
    $versions = new ReviewedPreprint($id, $json, ReviewedPreprint::DELETE);
  }

  /**
   * Test sample content generation.
   *
   * @test
   * @group journal-cms-tests
   */
  public function testGenerateSampleJson() {
    $reviewedPreprint = new ReviewedPreprint(19887, '');
    $reviewedPreprint->generateSampleJson();
    $this->assertEquals(json_decode(json_encode([
      'id' => '19887',
      'doi' => '10.7554/eLife.19887',
      'title' => 'Reviewed preprint 19887',
      'stage' => 'published',
      'published' => '2018-07-05T10:21:01Z',
      'reviewedDate' => '2018-07-05T10:21:01Z',
      'versionDate' => '2018-07-05T10:21:01Z',
      'statusDate' => '2018-07-05T10:21:01Z',
      'elocationId' => 'RP19887',
    ])), $reviewedPreprint->getJsonObject());
  }

}
