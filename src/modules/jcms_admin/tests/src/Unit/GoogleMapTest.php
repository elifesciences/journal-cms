<?php

namespace Drupal\Tests\jcms_admin\Unit;

use Drupal\jcms_admin\Embed;
use Drupal\jcms_admin\GoogleMap;
use Drupal\Tests\UnitTestCase;
use Embed\Adapters\Adapter;
use Embed\Providers\OpenGraph;
use Psr\Log\LoggerInterface;

/**
 * Tests for GoogleMap.
 */
class GoogleMapTest extends UnitTestCase {

  /**
   * Embed.
   *
   * @var \Drupal\jcms_admin\Embed
   */
  private $embed;

  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  /**
   * GoogleMap.
   *
   * @var \Drupal\jcms_admin\GoogleMap
   */
  private $googleMap;

  /**
   * Setup.
   *
   * @before
   */
  protected function setUp(): void {
    $this->embed = $this->createMock(Embed::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->googleMap = new GoogleMap($this->embed, $this->logger);
  }

  /**
   * Provider.
   */
  public function getIdFromUriProvider() : array {
    return [
      [
        '13cEQIsP3F9-iEVDDgradCs2Z9F-ODLyx',
        'https://www.google.com/maps/d/u/0/viewer?mid=13cEQIsP3F9-iEVDDgradCs2Z9F-ODLyx&ll=-3.81666561775622e-14%2C-94.2847887407595&z=1',
      ],
    ];
  }

  /**
   * It will get a Google map id from uri.
   *
   * @test
   * @dataProvider getIdFromUriProvider
   */
  public function itWillGetIdFromUri(string $expected, string $uri) {
    $this->assertEquals($expected, $this->googleMap->getIdFromUri($uri));
  }

  /**
   * It will get the title of the Google map.
   *
   * @test
   */
  public function itWillGetTitle() {
    $opengraph = $this->createMock(OpenGraph::class);
    $adapter = $this->createMock(Adapter::class);
    $opengraph
      ->expects($this->once())
      ->method('getTitle')
      ->willReturn('title');
    $adapter
      ->expects($this->once())
      ->method('getProviders')
      ->willReturn(['opengraph' => $opengraph]);
    $this->embed
      ->expects($this->once())
      ->method('create')
      ->with('https://www.google.com/maps/d/embed?mid=id')
      ->willReturn($adapter);
    $this->assertEquals('title', $this->googleMap->getTitle('id'));
  }

}
