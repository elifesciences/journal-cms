<?php

namespace Drupal\Tests\jcms_admin\Unit;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\jcms_admin\TransferContent;
use Drupal\jcms_rest\ValidatorInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for TransferContent.
 */
class TransferContentTest extends UnitTestCase {

  use Helper;

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
   * Validator.
   *
   * @var \Drupal\jcms_rest\ValidatorInterface
   */
  private $validator;

  /**
   * Setup.
   *
   * @before
   */
  protected function setUp(): void {
    $this->fileSystem = $this->createMock(FileSystemInterface::class);
    $this->renderer = $this->createMock(RendererInterface::class);
    $this->validator = $this->createMock(ValidatorInterface::class);
    $this->transferContent = new TransferContent($this->fileSystem, $this->renderer, $this->validator);
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
      'empty paragraphs only' => [
        '',
        $this->lines([
          '<p>&nbsp;</p>',
          '<p></p>',
          '<p> </p>',
          '<p>          </p>',
          "<p>\t</p>",
          "<p>\n</p>",
        ]),
      ],
      'empty paragraphs' => [
        $this->lines([
          '<p>Not empty 1</p>',
          '<p>Not empty 2</p>',
          '<p>Not empty 3</p>',
          '<p>Not empty 4</p>',
          '<p>Not empty 5</p>',
        ]),
        $this->lines([
          '<p>&nbsp;</p>',
          '<p>Not empty 1</p>',
          '<p></p>',
          '<p> </p>',
          '<p>          </p>',
          '<p>Not empty 2</p>',
          '<p>Not empty 3</p>',
          "<p>\t</p>",
          '<p>Not empty 4</p>',
          "<p>\n</p>",
          '<p>Not empty 5</p>',
        ]),
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
