<?php

namespace Drupal\jcms_ping\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Description.
 */
class PingController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * String sent in responses, to verify site status.
   *
   * @var string
   */
  const SITE_STATUS_RESPONSE = 'pong';

  /**
   * Checks the site status.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   */
  public function siteStatus() {
    $response = new Response(self::SITE_STATUS_RESPONSE);
    $response->headers->set('Content-Type', 'text/plain; charset=UTF-8');
    return $response;
  }

}
