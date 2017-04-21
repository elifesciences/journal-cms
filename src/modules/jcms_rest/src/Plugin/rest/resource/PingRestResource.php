<?php

namespace Drupal\jcms_rest\Plugin\rest\resource;

use Symfony\Component\HttpFoundation\Response;

/**
 * Provides a ping for smoke tests to call.
 *
 * @RestResource(
 *   id = "ping_rest_resource",
 *   label = @Translation("Ping rest resource"),
 *   uri_paths = {
 *     "canonical" = "/ping"
 *   }
 * )
 */
class PingRestResource extends AbstractRestResourceBase {
  /**
   * Responds to GET requests.
   *
   * @return Symfony\Component\HttpFoundation\Response
   */
  public function get(int $number) {
    return new Response(200, 'pong'); // TODO: add headers
  }

}
