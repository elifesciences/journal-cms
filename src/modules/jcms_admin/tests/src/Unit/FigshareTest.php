<?php

namespace Drupal\Tests\jcms_admin\Unit;

use Drupal\jcms_admin\Figshare;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for Figshare.
 */
class FigshareTest extends UnitTestCase {

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
  protected function setUp() {
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->figshare = new Figshare($this->logger);
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

}
