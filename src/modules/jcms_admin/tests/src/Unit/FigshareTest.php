<?php

namespace Drupal\Tests\jcms_admin\Unit;

use Drupal\jcms_admin\Embed;
use Drupal\jcms_admin\Figshare;
use Drupal\Tests\UnitTestCase;
use Embed\Adapters\Adapter;
use Embed\Providers\OpenGraph;
use Psr\Log\LoggerInterface;

/**
 * Tests for Figshare.
 */
class FigshareTest extends UnitTestCase {

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
   * @var \Drupal\jcms_admin\Figshare
   */
  private $figshare;

  /**
   * Setup.
   *
   * @before
   */
  protected function setUp(): void {
    $this->embed = $this->createMock(Embed::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->figshare = new Figshare($this->embed, $this->logger);
  }

  /**
   * Provider.
   */
  public function getIdFromUriProvider() : array {
    return [
      'main uri' => [
        '8210360',
        'https://figshare.com/articles/Shared_Open_Source_Infrastructure_with_the_Libero_Community/8210360',
      ],
      'embed uri' => [
        '8210360',
        'https://widgets.figshare.com/articles/8210360/embed?show_title=1',
      ],
    ];
  }

  /**
   * It will get a Figshare id from uri.
   *
   * @test
   * @dataProvider getIdFromUriProvider
   */
  public function itWillGetIdFromUri(string $expected, string $uri) {
    $this->assertEquals($expected, $this->figshare->getIdFromUri($uri));
  }

  /**
   * It will get the title of the Figshare.
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
      ->with('https://figshare.com/articles/og/id')
      ->willReturn($adapter);
    $this->assertEquals('title', $this->figshare->getTitle('id'));
  }

}
