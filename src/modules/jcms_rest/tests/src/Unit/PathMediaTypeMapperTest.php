<?php

namespace Drupal\Tests\jcms_rest\Unit;

use Drupal\jcms_rest\PathMediaTypeMapper;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for PathMediaTypeMapper.
 *
 * @package Drupal\Tests\jcms_rest\Unit
 */
class PathMediaTypeMapperTest extends UnitTestCase {

  /**
   * PathMediaTypeMapper.
   *
   * @var \Drupal\jcms_rest\PathMediaTypeMapper
   *   The class we're testing.
   */
  protected $pathMediaTypeMapper;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->pathMediaTypeMapper = new PathMediaTypeMapper();
  }

  /**
   * Provider for media type tests.
   */
  public function dataProvider() {
    // phpcs:disable
    return [
      ['/labs-posts', 'application/vnd.elife.labs-post-list+json'],
      ['/labs-posts/{number}', 'application/vnd.elife.labs-post+json'],
      ['/labs-posts/1234', 'application/vnd.elife.labs-post+json'],
      ['/people', 'application/vnd.elife.person-list+json'],
      ['/people/nathan-lisgo', 'application/vnd.elife.person+json'],
      ['/podcast-episodes', 'application/vnd.elife.podcast-episode-list+json'],
      ['/podcast-episodes/{number}', 'application/vnd.elife.podcast-episode+json'],
      ['/podcast-episodes/1234', 'application/vnd.elife.podcast-episode+json'],
      ['/subjects', 'application/vnd.elife.subject-list+json'],
      ['/subjects/{id}', 'application/vnd.elife.subject+json'],
      ['/subjects/plant-biology', 'application/vnd.elife.subject+json'],
      ['/subjects/', 'application/vnd.elife.subject-list+json'],
      ['/subjects/{id}/', 'application/vnd.elife.subject+json'],
      ['/bad-path', ''],
      ['/bad-path/{badplaceholder}', ''],
      ['/subjects/test/another-bad-path', ''],
      ['/subjects/{id}/another-bad-path', ''],
      ['/subjects/{id}/{anotherbadplaceholder}', ''],
    ];
    // phpcs:enable
  }

  /**
   * Test media type given the path.
   *
   * @test
   * @dataProvider dataProvider
   * @covers \Drupal\jcms_rest\PathMediaTypeMapper::getMappings
   * @covers \Drupal\jcms_rest\PathMediaTypeMapper::matchPathWithPlaceholders
   * @covers \Drupal\jcms_rest\PathMediaTypeMapper::getMediaTypeByPath
   * @group journal-cms-tests
   */
  public function testGetMediaTypeByPath($path, $expected) {
    $actual = $this->pathMediaTypeMapper->getMediaTypeByPath($path);
    $this->assertEquals($expected, $actual);
  }

}
