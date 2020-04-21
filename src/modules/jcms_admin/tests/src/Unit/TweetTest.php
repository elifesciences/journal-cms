<?php

namespace Drupal\Tests\jcms_admin\Unit;

use Drupal\jcms_admin\Embed;
use Drupal\jcms_admin\Tweet;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for Tweet.
 */
class TweetTest extends UnitTestCase {

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
   * Tweet.
   *
   * @var \Drupal\jcms_admin\Tweet
   */
  private $tweet;

  /**
   * Setup.
   *
   * @before
   */
  protected function setUp() {
    $this->embed = $this->createMock(Embed::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->tweet = new Tweet($this->embed, $this->logger);
  }

  /**
   * Provider.
   */
  public function getIdFromUriProvider() : array {
    return [
      [
        '1244671264595288065',
        'https://twitter.com/eLife/status/1244671264595288065',
      ],
    ];
  }

  /**
   * It will get a Twitter status id from uri.
   *
   * @test
   * @dataProvider getIdFromUriProvider
   */
  public function itWillGetIdFromUri(string $expected, string $uri) {
    $this->assertEquals($expected, $this->tweet->getIdFromUri($uri));
  }

}
