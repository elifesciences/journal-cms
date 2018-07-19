<?php

namespace Drupal\Tests\jcms_admin\Unit;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\jcms_admin\TransferContent;
use PHPUnit\Framework\TestCase;

/**
 * Tests for TransferContent.
 */
class TransferContentTest extends TestCase {

  /**
   * File system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  private $fileSystem;

  /**
   * Renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  private $renderer;

  /**
   * TransferContent.
   *
   * @var \Drupal\jcms_admin\TransferContent
   */
  private $transferContent;

  /**
   * Setup.
   *
   * @before
   */
  protected function setUp() {
    $this->fileSystem = $this->getMock(FileSystemInterface::class);
    $this->renderer = $this->getMock(RendererInterface::class);
    $this->transferContent = new TransferContent($this->fileSystem, $this->renderer);
  }

  /**
   * Provider.
   */
  public function stripEmptyParagraphsProvider() : array {
    return [
      'empty' => [
        '',
        '',
      ],
    ];
  }

  /**
   * It will strip empty paragraphs.
   *
   * @test
   * @dataProvider stripEmptyParagraphsProvider
   */
  public function itWillStripEmptyParagraphs(string $expected, string $html) {
    $this->assertEquals($expected, $this->transferContent->stripEmptyParagraphs($html));
  }

}
