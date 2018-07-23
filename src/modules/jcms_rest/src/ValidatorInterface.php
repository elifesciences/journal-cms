<?php

namespace Drupal\jcms_rest;

use Drupal\node\NodeInterface;

/**
 * Validate whether the content matches the schema.
 */
interface ValidatorInterface {

  /**
   * Validate the content.
   *
   * @throws InvalidMessage
   */
  public function validate(NodeInterface $node, $preview = FALSE, $context = []);

}
