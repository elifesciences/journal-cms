<?php

namespace Drupal\jcms_admin\Controller;

use Drupal\system\Controller\EntityAutocompleteController as CoreEntityAutocompleteController;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * {@inheritdoc}
 */
class EntityAutocompleteController extends CoreEntityAutocompleteController {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('jcms_admin.autocomplete_matcher'),
      $container->get('keyvalue')->get('entity_autocomplete')
    );
  }

}
