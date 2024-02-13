<?php

namespace Drupal\jcms_admin;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\jcms_rest\ValidatorInterface;
use Drupal\node\NodeInterface;

/**
 * Transfer content from preview to live and vice versa.
 */
final class TransferContent {

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  private $fileSystem;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  private $renderer;

  /**
   * The Validator.
   *
   * @var \Drupal\jcms_rest\ValidatorInterface
   */
  private $validator;

  /**
   * Constructor.
   */
  public function __construct(FileSystemInterface $fileSystem, RendererInterface $renderer, ValidatorInterface $validator) {
    $this->fileSystem = $fileSystem;
    $this->renderer = $renderer;
    $this->validator = $validator;
  }

  /**
   * Transfer html from preview to live fields and vice versa.
   *
   * @throws \eLife\ApiValidator\Exception\InvalidMessage
   */
  public function transfer(NodeInterface $node, $toLive = TRUE, $validate = FALSE, $context = []) : NodeInterface {
    if ($node->hasField('field_content_json') && $node->hasField('field_content_json_preview')) {
      if (!$node->isNew() && $validate) {
        $this->validator->validate($node, $toLive, $context);
      }
      if ($toLive) {
        $fromHtml = $this->cleanHtmlField($node->get('field_content_html_preview'));
        $fromImages = $node->get('field_content_images_preview')->referencedEntities();
        $toHtmlField = 'field_content_html';
        $toImageField = 'field_content_images';
      }
      else {
        $fromHtml = $this->cleanHtmlField($node->get('field_content_html'));
        $fromImages = $node->get('field_content_images')->referencedEntities();
        $toHtmlField = 'field_content_html_preview';
        $toImageField = 'field_content_images_preview';
      }

      $toFids = [];
      $fids = [];
      /** @var \Drupal\file\FileInterface $image */
      foreach ($fromImages as $image) {
        $fromFid = $image->id();
        $newImageUri = preg_replace('~(/[a-z+\-]+\-)(content|preview)/~', '$1' . ($toLive ? 'content' : 'preview') . '/', $image->getFileUri());
        $directory = $this->fileSystem->dirname($newImageUri);
        \Drupal::service('file_system')->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);
        $newImage = \Drupal::service('file.repository')->copy($image, $newImageUri, FileSystemInterface::EXISTS_RENAME);
        $newImage->save();
        $toFids[] = ['target_id' => $newImage->id()];
        $fids[$fromFid] = [
          'fid' => $newImage->id(),
          'uuid' => $newImage->uuid(),
          'src' => \Drupal::service('file_url_generator')->generateString($newImage->getFileUri()),
        ];
      }

      $toHtml = preg_replace_callback('~<img [^>]*data-fid=\"(?P<fid>[^\"]+)\"[^>]+>~', function ($match) use ($fids) {
        if (!empty($fids[$match['fid']])) {
          $new = $fids[$match['fid']];
          $pattern = [
            '~ data-fid="[^\"]+\"~',
            '~ data-uuid="[^\"]+\"~',
            '~ src="[^\"]+\"~',
          ];
          $replacement = [
            ' data-fid="' . $new['fid'] . '"',
            ' data-uuid="' . $new['uuid'] . '"',
            ' src="' . $new['src'] . '"',
          ];
          return preg_replace($pattern, $replacement, $match[0]);
        }
        else {
          return $match[0];
        }
      }, $fromHtml);

      $node->set($toImageField, $toFids);
      $node->set($toHtmlField, [
        'value' => $toHtml,
        'format' => 'ckeditor_html',
      ]);
    }

    return $node;
  }

  /**
   * Clean HTML for field and return HTML string.
   */
  public function cleanHtmlField(FieldItemListInterface $data) : string {
    $view = $data->view();
    unset($view['#theme']);
    $output = $this->renderer->renderPlain($view);
    return $this->stripEmptyParagraphs($output);
  }

  /**
   * Strip empty paragraphs.
   */
  public function stripEmptyParagraphs(string $html) : string {
    return trim(preg_replace(['~[\s\r\n\t]*<p>(&nbsp;|[\s\r\n\t]*)</p>~'], '', $html));
  }

}
