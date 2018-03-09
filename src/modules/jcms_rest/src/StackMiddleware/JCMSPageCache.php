<?php

namespace Drupal\jcms_rest\StackMiddleware;

use Drupal\jcms_rest\PathMediaTypeMapper;
use Drupal\page_cache\StackMiddleware\PageCache;
use Symfony\Component\HttpFoundation\Request;

/**
 * Extend \Drupal\page_cache\StackMiddleware\PageCache.
 *
 * We want to allow manipulation of the cache id.
 */
class JCMSPageCache extends PageCache {

  /**
   * {@inheritdoc}
   */
  protected function getCacheId(Request $request) {
    $format = $request->getRequestFormat();
    // If accept header has not been recognised we may still process as
    // jcms_json if we recognise the path.
    if ($format != 'jcms_json') {
      $mapper = new PathMediaTypeMapper();
      if ($content_type = $mapper->getMediaTypeByPath($request->getPathInfo())) {
        $format = 'jcms_json';
      }
    }
    $cid_parts = [
      $request->getSchemeAndHttpHost() . $request->getRequestUri(),
      $format,
    ];

    // If jcms_json then add specific headers to cache id.
    if ($format == 'jcms_json') {
      $headers = [];
      foreach ([
        'X-Consumer-Groups',
        'Accept',
        'If-None-Match',
        'If-Modified-Since',
      ] as $header) {
        if ($request->headers->has($header)) {
          $headers[] = $header . ': ' . $request->headers->get($header);
        }
      }
      if (!empty($headers)) {
        $cid_parts[] = md5(implode('|', $headers));
      }
    }
    return implode(':', $cid_parts);
  }

}
