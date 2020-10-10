<?php

namespace Drupal\jcms_digest\Tests\Unit\Entity;

use Drupal\Tests\UnitTestCase;
use Drupal\jcms_digest\Entity\Digest;

/**
 * Tests for Digest.
 *
 * @package Drupal\jcms_digest\Tests\Unit\Entity
 */
class DigestTest extends UnitTestCase {

  /**
   * Test basic getters.
   *
   * @test
   * @group journal-cms-tests
   */
  public function testBasicGetters() {
    $id = 'id';
    $json = '{}';
    $digest = new Digest($id, $json, Digest::DELETE);
    $this->assertEquals($id, $digest->getId());
    $this->assertEquals($json, $digest->getJson());
    $this->assertEquals(Digest::DELETE, $digest->getAction());
    $this->assertInstanceOf('stdClass', $digest->getJsonObject());
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
    new Digest($id, $json, Digest::DELETE);
  }

  /**
   * Test sample content generation.
   *
   * @test
   * @group journal-cms-tests
   */
  public function testGenerateSampleJson() {
    $digest = new Digest(19887, '');
    $digest->generateSampleJson();
    $this->assertEquals(json_decode(json_encode([
      'id' => '19887',
      'title' => 'Digest 19887',
      'stage' => 'published',
      'published' => '2018-07-05T10:21:01Z',
      'updated' => '2018-07-05T10:21:01Z',
      'image' => [
        'thumbnail' => [
          'uri' => 'https://iiif.elifesciences.org/digests/19887%2Fdigest-19887.jpg',
          'alt' => '',
          'source' => [
            'uri' => 'https://iiif.elifesciences.org/digests/19887%2Fdigest-19887.jpg/full/full/0/default.jpg',
            'filename' => 'digest-19887.jpg',
            'mediaType' => 'image/jpeg',
          ],
          'size' => [
            'width' => 1920,
            'height' => 1421,
          ],
        ],
      ],

    ])), $digest->getJsonObject());
  }

}
