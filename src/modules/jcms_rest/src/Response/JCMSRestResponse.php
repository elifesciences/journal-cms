<?php

namespace Drupal\jcms_rest\Response;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableResponseInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Cache\CacheableResponseTrait;

class JCMSRestResponse extends JsonResponse implements CacheableResponseInterface {

  use CacheableResponseTrait;

  /**
   * {@inheritdoc}
   */
  public function __construct($data = NULL, $status = 200, $headers = []) {
    parent::__construct($data, $status, $headers);
    $this->addDefaultCacheableDependencies();
  }

  /**
   * Allows multiple items to be added as cacheable dependencies.
   *
   * @param array $dependencies
   */
  public function addCacheableDependencies(array $dependencies) {
    foreach ($dependencies as $dependency) {
      $this->addCacheableDependency($dependency);
    }
  }

  /**
   * Adds default cacheable dependencies such as query string parameters.
   *
   * @todo In future this may need to handle versioning from Accept headers.
   */
  public function addDefaultCacheableDependencies() {
    $build = [
      '#cache' => [
        'contexts' => ['url.query_args'],
      ],
    ];
    $cache_metadata = CacheableMetadata::createFromRenderArray($build);
    $this->addCacheableDependency($cache_metadata);
  }

}
