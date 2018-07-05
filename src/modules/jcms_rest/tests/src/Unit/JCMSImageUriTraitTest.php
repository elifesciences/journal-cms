<?php

namespace Drupal\Tests\jcms_rest\Unit;

use Drupal\Core\Site\Settings;
use Drupal\jcms_rest\JCMSImageUriTrait;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for methods in JCMSImageUriTrait.
 *
 * @package Drupal\Tests\jcms_rest\Unit
 */
class JCMSImageUriTraitTest extends UnitTestCase {

  use JCMSImageUriTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->settings = new Settings([
      'jcms_iiif_base_uri' => 'https://prod--iiif.elifesciences.org/journal-cms:',
      'jcms_iiif_mount' => 'iiif',
    ]);
  }

  /**
   * Provider for processImageUri tests.
   */
  public function providerProcessImageUris() {
    return [
      [
        'public://iiif/content/2018-07/image.jpg',
        'https://prod--iiif.elifesciences.org/journal-cms:content%2F2018-07%2Fimage.jpg/full/full/0/default.jpg',
      ],
      [
        'public://iiif/inside-elife/2017-08/banner.png',
        'https://prod--iiif.elifesciences.org/journal-cms:inside-elife%2F2017-08%2Fbanner.png/full/full/0/default.png',
        'image/png',
      ],
    ];
  }

  /**
   * Test output of processImageUri method.
   *
   * @test
   * @dataProvider providerProcessImageUris
   * @covers \Drupal\jcms_rest\JCMSImageUriTrait::processImageUri()
   * @group journal-cms-tests
   */
  public function testProcessImageUri(string $image_uri, string $expected, string $filename = 'image/jpeg', string $source = 'source') {
    $this->assertEquals($expected, $this->processImageUri($image_uri, $source, $filename));
  }

}
