<?php

namespace Drupal\jcms_rest\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route event.
 *
 * @package Drupal\jcms_rest\Routing
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();

    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -9999];

    return $events;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach ($collection as $name => $route) {
      // If the route matches a JCMS rest endpoint.
      if (preg_match("/^rest.[a-z_]+.[A-Z]+.jcms_json$/", $name)) {
        // Remove the request_format_route_filter from the route _filters.
        $route_filters = $route->getOption('_route_filters') ?: [];
        $route_filters = array_filter($route_filters, function ($route_filter) {
          return $route_filter != 'request_format_route_filter';
        });
        // Re-index the array.
        $route_filters = array_values($route_filters);
        $route->setOption('_route_filters', $route_filters);
      }
    }
  }

}
