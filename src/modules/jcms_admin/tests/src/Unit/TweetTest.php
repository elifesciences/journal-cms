<?php

namespace Drupal\Tests\jcms_admin\Unit;

use Drupal\jcms_admin\Embed;
use Drupal\jcms_admin\Tweet;
use Drupal\Tests\UnitTestCase;
use Embed\Adapters\Adapter;
use Embed\Providers\OEmbed;
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

  /**
   * It will get the details of the Tweet.
   *
   * @test
   */
  public function itWillGetDetails() {
    $oembed = $this->createMock(OEmbed::class);
    $adapter = $this->createMock(Adapter::class);
    $oembed
      ->expects($this->once())
      ->method('getCode')
      ->willReturn('<blockquote><p>text</p>&mdash; accountLabel (@accountId) <a href="https://twitter.com/accountId/status/id">April 20, 2020</a></blockquote>');
    $oembed
      ->expects($this->once())
      ->method('getAuthorName')
      ->willReturn('accountLabel');
    $adapter
      ->expects($this->once())
      ->method('getProviders')
      ->willReturn([
        'oembed' => $oembed,
      ]);
    $this->embed
      ->expects($this->once())
      ->method('create')
      ->with('https://twitter.com/accountId/status/id')
      ->willReturn($adapter);
    $this->assertEquals([
      'date' => 1587304800,
      'accountId' => 'accountId',
      'accountLabel' => 'accountLabel',
      'text' => 'text',
    ], $this->tweet->getDetails('https://twitter.com/accountId/status/id'));
  }

}
