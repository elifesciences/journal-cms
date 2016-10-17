<?php

namespace Drupal\jcms_rest\Routing;

use Drupal\Core\Routing\RouteFilterInterface;
use Drupal\jcms_rest\PathMimeTypeMapper;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class JcmsRestResourceRouteFilter.
 *
 * @package Drupal\jcms_rest
 */
class JcmsRestResourceRouteFilter implements RouteFilterInterface {

  /**
   * @var \Drupal\jcms_rest\PathMimeTypeMapper
   */
  protected $pathMimeTypeMapper;

  /**
   * JcmsRestResourceRouteFilter constructor.
   *
   * @param \Drupal\jcms_rest\PathMimeTypeMapper $path_mime_type_mapper
   */
  public function __construct(PathMimeTypeMapper $path_mime_type_mapper) {
    $this->pathMimeTypeMapper = $path_mime_type_mapper;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(Route $route) {
    return $this->pathMimeTypeMapper->getMimeTypeByPath($route->getPath());
  }

  /**
   * {@inheritdoc}
   */
  public function filter(RouteCollection $collection, Request $request) {
    $accept_header = '';
    /** @var \Symfony\Component\Routing\Route $route */
    foreach ($collection as $name => $route) {
      // If the accept header matches the correct JCMS mime type.
      $path = $request->getPathInfo();
      $accept_header = $request->headers->get('Accept');
      $acceptable_mime_type = $this->pathMimeTypeMapper->getMimeTypeByPath($path);
      /*
      error_log("new HTTP request");
      error_log($path);
      error_log($accept_header);
      $e = new \Exception();
      error_log($e->getTraceAsString());
      */
      if (AcceptHeader::fromString($accept_header)->get($acceptable_mime_type)) {
        $collection->add($name, $route);
      }
      else {
        $collection->remove($name);
      }
    }
    if (count($collection)) {
      return $collection;
    }
    // We do not throw a
    // \Symfony\Component\Routing\Exception\ResourceNotFoundException here
    // because we don't want to return a 404 status code, but rather a 406.
    throw new NotAcceptableHttpException("No route found for the specified accept header $accept_header.");
  }

}
