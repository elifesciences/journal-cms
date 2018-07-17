<?php

namespace Drupal\jcms_admin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;

/**
 * Controller to reset content for inline ckeditor.
 */
class ResetContentController extends ControllerBase {

  /**
   * Discard node preview content.
   */
  public function reset(NodeInterface $node) {
    $node = \Drupal::service('jcms_admin.transfer_content')->transfer($node, FALSE);
    $node->save();
    $this->messenger()->addMessage($this->t('Draft content has been discarded.'));
    return $this->redirect('entity.node.canonical', ['node' => $node->id()]);
  }

}
