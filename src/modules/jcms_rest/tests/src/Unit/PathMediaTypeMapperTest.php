<?php

namespace Drupal\jcms_rest\Tests\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\jcms_rest\PathMediaTypeMapper;

/**
 * Class PathMediaTypeMapperTest
 *
 * @package Drupal\Tests\jcms_rest\Unit
 */
class PathMediaTypeMapperTest extends UnitTestCase {

  /**
   * @var \Drupal\jcms_rest\PathMediaTypeMapper
   *   The class we're testing.
   */
  protected $pathMediaTypeMapper;

  public function setUp() {
    $this->pathMediaTypeMapper = new PathMediaTypeMapper();
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
   * @covers \Drupal\jcms_rest\PathMediaTypeMapper::getMappings
   * @covers \Drupal\jcms_rest\PathMediaTypeMapper::matchPathWithPlaceholders
   * @covers \Drupal\jcms_rest\PathMediaTypeMapper::getMediaTypeByPath
   * @group  journal-cms-tests
   */
  public function testGetMediaTypeByPath($path, $expected) {
    $actual = $this->pathMediaTypeMapper->getMediaTypeByPath($path);
    $this->assertEquals($expected, $actual);
  }

}
