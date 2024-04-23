<?php

namespace Drupal\Tests\jcms_admin\Unit;

use Drupal\jcms_admin\Tweet;
use Drupal\media\OEmbed\Resource;
use Drupal\media\OEmbed\ResourceFetcher;
use Drupal\media\OEmbed\UrlResolver;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for Tweet.
 */
class TweetTest extends UnitTestCase {

  /**
   * The Oembed Url Resolver.
   *
   * @var \Drupal\media\OEmbed\UrlResolver
   */
  private $urlResolver;

  /**
   * The Oembed Resource Fetcher.
   *
   * @var \Drupal\media\OEmbed\ResourceFetcher
   */
  private $resourceFetcher;

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
  protected function setUp(): void {
    $this->urlResolver = $this->createMock(UrlResolver::class);
    $this->resourceFetcher = $this->createMock(ResourceFetcher::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->tweet = new Tweet($this->logger, $this->urlResolver, $this->resourceFetcher);
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
    $resource = $this->createMock(Resource::class);
    $resource
      ->expects($this->once())
      ->method('getHtml')
      ->willReturn('<blockquote><p>text</p>&mdash; accountLabel (@accountId) <a href="https://twitter.com/eLife/status/id">April 20, 2020</a></blockquote>');
    $resource
      ->expects($this->once())
      ->method('getAuthorName')
      ->willReturn('accountLabel');
    $uri = 'https://twitter.com/og/status/id';
    $this->urlResolver
      ->expects($this->once())
      ->method('getResourceUrl')
      ->with($uri)
      ->willReturn($uri);
    $this->resourceFetcher
      ->expects($this->once())
      ->method('fetchResource')
      ->with($uri)
      ->willReturn($resource);
    $this->assertEquals([
      'date' => 1587304800,
      'accountId' => 'accountId',
      'accountLabel' => 'accountLabel',
      'text' => 'text',
    ], $this->tweet->getDetails('id'));
  }

}
