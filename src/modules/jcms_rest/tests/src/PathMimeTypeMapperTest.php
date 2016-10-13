<?php

namespace Drupal\Tests\jcms_rest\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\jcms_rest\PathMimeTypeMapper;

/**
 * Class PathMimeTypeMapperTest
 *
 * @package Drupal\Tests\jcms_rest\Unit
 */
class PathMimeTypeMapperTest extends UnitTestCase {

  /**
   * @var \Drupal\jcms_rest\PathMimeTypeMapper
   *   The class we're testing.
   */
  protected $pathMimeTypeMapper;

  public function setUp() {
    $this->pathMimeTypeMapper = new PathMimeTypeMapper();
  }

  public function dataProvider()
  {
    return [
      ['/labs-experiments', 'application/vnd.elife.labs-experiment-list+json'],
      ['/labs-experiments/{number}', 'application/vnd.elife.labs-experiment+json'],
      ['/labs-experiments/1234', 'application/vnd.elife.labs-experiment+json'],
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
  }

  /**
   * @test
   * @dataProvider dataProvider
   * @covers \Drupal\jcms_rest\PathMimeTypeMapper::getMappings
   * @covers \Drupal\jcms_rest\PathMimeTypeMapper::matchPathWithPlaceholders
   * @covers \Drupal\jcms_rest\PathMimeTypeMapper::getMimeTypeByPath
   * @group  journal-cms-tests
   */
  public function testGetMimeTypeByPath($path, $expected) {
    $actual = $this->pathMimeTypeMapper->getMimeTypeByPath($path);
    $this->assertEquals($expected, $actual);
  }

}
