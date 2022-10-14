<?php

namespace Drupal\Tests\jcms_admin\Unit;

use Drupal\jcms_admin\Embed;
use Drupal\jcms_admin\YouTube;
use Drupal\Tests\UnitTestCase;
use Embed\Adapters\Adapter;
use Embed\Providers\OpenGraph;
use Psr\Log\LoggerInterface;

/**
 * Tests for YouTube.
 */
class YouTubeTest extends UnitTestCase {

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
   * Figshare.
   *
   * @var \Drupal\jcms_admin\YouTube
   */
  private $youtube;

  /**
   * Setup.
   *
   * @before
   */
  protected function setUp(): void {
    $this->embed = $this->createMock(Embed::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->youtube = new YouTube($this->embed, $this->logger);
  }

  /**
   * Provider.
   */
  public function getIdFromUriProvider() : array {
    return [
      'main uri' => [
        'kIJLyqct6Fo',
        'https://www.youtube.com/watch?v=kIJLyqct6Fo',
      ],
      'embed uri' => [
        'kIJLyqct6Fo',
        'https://www.youtube.com/embed/kIJLyqct6Fo',
      ],
    ];
  }

  /**
   * It will get a YouTube id from uri.
   *
   * @test
   * @dataProvider getIdFromUriProvider
   */
  public function itWillGetIdFromUri(string $expected, string $uri) {
    $this->assertEquals($expected, $this->youtube->getIdFromUri($uri));
  }

  /**
   * It will get the dimension defaults if we cannot reach YouTube.
   *
   * @test
   */
  public function itWillGetDimensionDefaultsIfCannotReachYouTube() {
    $this->embed
      ->expects($this->once())
      ->method('create')
      ->with('https://www.youtube.com/watch?v=id')
      ->willThrowException(new \Exception('YouTube down!'));
    $this->logger
      ->expects($this->once())
      ->method('error')
      ->with('YouTube could not be reached.', ['id' => 'id']);
    $this->assertEquals([
      'width' => 16,
      'height' => 9,
    ], $this->youtube->getDimensions('id'));
  }

  /**
   * It will get the dimensions of the YouTube video.
   *
   * @test
   */
  public function itWillGetDimensions() {
    $opengraph = $this->createMock(OpenGraph::class);
    $adapter = $this->createMock(Adapter::class);
    $opengraph
      ->expects($this->once())
      ->method('getWidth')
      ->willReturn('1000');
    $opengraph
      ->expects($this->once())
      ->method('getHeight')
      ->willReturn('500');
    $adapter
      ->expects($this->once())
      ->method('getProviders')
      ->willReturn(['opengraph' => $opengraph]);
    $this->embed
      ->expects($this->once())
      ->method('create')
      ->with('https://www.youtube.com/watch?v=id')
      ->willReturn($adapter);
    $this->assertEquals([
      'width' => 1000,
      'height' => 500,
    ], $this->youtube->getDimensions('id'));
  }

}
