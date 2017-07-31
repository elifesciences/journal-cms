<?php

namespace Drupal\jcms_rest;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;

/**
 * Adds application/vnd.elife.annual-report-list+json as a known format.
 * This must be named this way to ensure its discovery.
 */
class JCMSRestServiceProvider implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $modules = $container->getParameter('container.modules');

    if ($container->has('http_middleware.negotiation') && is_a($container->getDefinition('http_middleware.negotiation')->getClass(), '\Drupal\Core\StackMiddleware\NegotiationMiddleware', TRUE)) {
      $container->getDefinition('http_middleware.negotiation')->addMethodCall('registerFormat', ['jcms_json', array_values(PathMediaTypeMapper::getMappings())]);
    }

    // Alter the http_middleware.page_cache service only if Internal Page Cache module is enabled.
    if (isset($modules['page_cache'])) {
      $container->getDefinition('http_middleware.page_cache')
        ->setClass('Drupal\jcms_rest\StackMiddleware\PageCache');
    }

    $definition = $container->getDefinition('response_generator_subscriber');
    $definition->setClass('Drupal\jcms_rest\EventSubscriber\ResponseGeneratorSubscriber')
      ->clearTags();
  }

}
