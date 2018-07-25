<?php

namespace Drupal\jcms_admin\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;

class EntityAutocompleteController extends \Drupal\system\Controller\EntityAutocompleteController {

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
