<?php

namespace Drupal\jcms_rest\Routing;

use Drupal\Core\Routing\RouteFilterInterface;
use Drupal\jcms_rest\Exception\JCMSNotAcceptableHttpException;
use Drupal\jcms_rest\PathMediaTypeMapper;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class JcmsRestResourceRouteFilter.
 *
 * @package Drupal\jcms_rest
 */
class JcmsRestResourceRouteFilter implements RouteFilterInterface {

  /**
   * @var \Drupal\jcms_rest\PathMediaTypeMapper
   */
  protected $pathMediaTypeMapper;

  /**
   * JcmsRestResourceRouteFilter constructor.
   *
   * @param \Drupal\jcms_rest\PathMediaTypeMapper $path_media_type_mapper
   */
  public function __construct(PathMediaTypeMapper $path_media_type_mapper) {
    $this->pathMediaTypeMapper = $path_media_type_mapper;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(Route $route) {
    return $this->pathMediaTypeMapper->getMediaTypeByPath($route->getPath());
  }

  /**
   * {@inheritdoc}
   */
  public function filter(RouteCollection $collection, Request $request) {
    // Set the request format to ensure only JSON is ever rendered.
    $request->setRequestFormat('jcms_json');
    // If the accept header matches the correct JCMS media type.
    $path = $request->getPathInfo();
    $accept_header = $request->headers->get('Accept');
    $acceptable_media_type = $this->pathMediaTypeMapper->getMediaTypeByPath($path);
    /** @var \Symfony\Component\Routing\Route $route */
    foreach ($collection as $name => $route) {
      if (AcceptHeader::fromString($accept_header)->get($acceptable_media_type)) {
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
    throw new JCMSNotAcceptableHttpException("No route found for the specified accept header $accept_header.", NULL, $acceptable_media_type);
  }

}
