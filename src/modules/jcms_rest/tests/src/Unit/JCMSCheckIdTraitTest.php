<?php

namespace Drupal\Tests\jcms_rest\Unit;

use Drupal\jcms_rest\JCMSCheckIdTrait;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for methods in JCMSCheckIdTrait.
 *
 * @package Drupal\Tests\jcms_rest\Unit
 */
class JCMSCheckIdTraitTest extends UnitTestCase {

  use JCMSCheckIdTrait;

  /**
   * Provider for check Id tests.
   */
  public function providerCheckId() {
    return [
      'valid-id' => [
        'a1b2c3d4',
        NULL,
        TRUE,
      ],
      'id-too-short' => [
        '7777777',
        NULL,
        FALSE,
      ],
      'id-too-long' => [
        '999999999',
        NULL,
        FALSE,
      ],
      'id-not-lowercase' => [
        'UPPERCAS',
        NULL,
        FALSE,
      ],
      'id-invalid-chars' => [
        '1111111g',
        NULL,
        FALSE,
      ],
      'id-podcast-episode' => [
        10,
        'podcast-episode',
        TRUE,
      ],
      'id-article' => [
        'aritcle-id',
        'article',
        TRUE,
      ],
      'id-article-not-lowercase' => [
        'UPPERCASE',
        'article',
        FALSE,
      ],
      'id-article-invalid-characters' => [
        '!!',
        'article',
        FALSE,
      ],
      'id-digest' => [
        'digest-id',
        'digest',
        TRUE,
      ],
      'id-digest-not-lowercase' => [
        'UPPERCASE',
        'digest',
        FALSE,
      ],
      'id-digest-invalid-characters' => [
        '!!',
        'digest',
        FALSE,
      ],
      'id-subject' => [
        'subject-id',
        'subject',
        TRUE,
      ],
      'id-subject-not-lowercase' => [
        'UPPERCASE',
        'subject',
        FALSE,
      ],
      'id-subject-invalid-characters' => [
        '!!',
        'subject',
        FALSE,
      ],
      'id-annual-report-less-than-2012' => [
        2011,
        'annual-report',
        FALSE,
      ],
      'id-annual-report-2012' => [
        2012,
        'annual-report',
        TRUE,
      ],
      'id-annual-report-9999' => [
        9999,
        'annual-report',
        TRUE,
      ],
      'id-annual-report-more-than-9999' => [
        10000,
        'annual-report',
        FALSE,
      ],
    ];
  }

  /**
   * Test output of check Id method.
   *
   * @test
   * @dataProvider providerCheckId
   * @covers \Drupal\jcms_rest\JCMSCheckIdTrait::checkId
   * @group journal-cms-tests
   */
  public function testCheckId($id, $type, $expected) {
    $this->assertEquals($expected, $this->checkId($id, $type));
  }

}
