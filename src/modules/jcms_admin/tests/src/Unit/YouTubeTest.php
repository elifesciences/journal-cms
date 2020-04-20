<?php

namespace Drupal\Tests\jcms_admin\Unit;

use Drupal\jcms_admin\YouTube;
use Drupal\Tests\UnitTestCase;
use Embed\Embed;
use Psr\Log\LoggerInterface;

/**
 * Tests for YouTube.
 */
class YouTubeTest extends UnitTestCase {

  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  /**
   * Embed.
   *
   * @var \Embed\Embed
   */
  private $embed;

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
  protected function setUp() {
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

}
