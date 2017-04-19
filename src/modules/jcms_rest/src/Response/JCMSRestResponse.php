<?php

namespace Drupal\jcms_rest\Response;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Site\Settings;
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
    $request = \Drupal::request();
    $consumer = $request->headers->get('X-Consumer-Groups', 'user');
    $this->setVary('Accept');
    $max_age = ($consumer == 'admin') ? 0 : Settings::get('jcms_rest_cache_max_age', Cache::PERMANENT);

    $build = [
      '#cache' => [
        'contexts' => ['url', 'user.permissions', 'headers:X-Consumer-Groups', 'headers:Accept'],
        'max-age' => $max_age,
      ],
    ];

    $cache_metadata = CacheableMetadata::createFromRenderArray($build);
    $this->addCacheableDependency($cache_metadata);

    $this->headers->addCacheControlDirective('max-age', $max_age);

    if ($consumer == 'admin') {
      $this->setPrivate();
    }
    else {
      $this->setPublic();
      $this->headers->addCacheControlDirective('stale-while-revalidate', 300);
      $this->headers->addCacheControlDirective('stale-if-error', 86400);
      $this->setEtag(md5($this->getContent()));
      $this->isNotModified($request);
    }

  }

}
