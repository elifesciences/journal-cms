<?php

namespace Drupal\jcms_admin\Plugin\views\field;

use Drupal\Core\Link;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to present the path to the content on journal.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("jcms_content_link")
 */
class ContentLink extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    /** @var \Drupal\node\NodeInterface $content */
    $content = $values->_entity;
    $journal_path = Settings::get('journal_path');
    $journal_preview = Settings::get('journal_preview');
    $path = \Drupal::service('jcms_admin.content_path')->create($content);
    if (!$path || !$journal_path) {
      return '';
    }
    else {
      $links = [
        [
          '#theme' => 'clipboard_simple',
          '#text' => $journal_path . $path,
        ],
      ];
      if ($journal_preview) {
        $links[] = [
          '#theme' => 'markup',
          '#markup' => Link::fromTextAndUrl(t('Preview page'), Url::fromUri($journal_preview . $path)),
        ];
        $links = [
          '#theme' => 'item_list',
          '#items' => $links,
          '#attributes' => [
            'class' => [
              'clipboard-simple-list',
            ],
          ],
        ];
      }
      return $links;
    }
  }

}
