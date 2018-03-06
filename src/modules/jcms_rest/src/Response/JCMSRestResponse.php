<?php

namespace Drupal\jcms_rest\Response;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Site\Settings;
use function GuzzleHttp\Psr7\normalize_header;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Cache\CacheableResponseTrait;

/**
 * Class JCMSRestResponse.
 */
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
   */
  public function addCacheableDependencies(array $dependencies) {
    foreach ($dependencies as $dependency) {
      $this->addCacheableDependency($dependency);
    }
  }

  /**
   * Adds default cacheable dependencies such as query string parameters.
   */
  public function addDefaultCacheableDependencies() {
    $request = \Drupal::request();
    $groups = normalize_header($request->headers->get('X-Consumer-Groups', 'user'));
    $view_unpublished = in_array('view-unpublished-content', $groups);
    $this->setVary('Accept');
    $max_age = $view_unpublished ? 0 : Settings::get('jcms_rest_cache_max_age', Cache::PERMANENT);

    $build = [
      '#cache' => [
        'contexts' => ['url',
          'user.permissions',
          'headers:X-Consumer-Groups',
          'headers:Accept',
          'headers:If-None-Match',
          'headers:If-Modified-Since',
        ],
        'max-age' => $max_age,
      ],
    ];

    $cache_metadata = CacheableMetadata::createFromRenderArray($build);
    $this->addCacheableDependency($cache_metadata);

    $this->headers->addCacheControlDirective('max-age', $max_age);

    if ($view_unpublished) {
      $this->setPrivate();
      $this->headers->addCacheControlDirective('must-revalidate');
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
