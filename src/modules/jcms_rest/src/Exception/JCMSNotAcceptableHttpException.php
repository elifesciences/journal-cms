<?php

namespace Drupal\jcms_rest\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * This should be used instead of the Symfony NotAcceptableHttpException.
 *
 * We need this to set the content type header.
 *
 * @package Drupal\jcms_rest\Exception
 */
class JCMSNotAcceptableHttpException extends HttpException {

  /**
   * JCMSNotAcceptableHttpException constructor.
   */
  public function __construct($message, \Exception $previous = NULL, $media_type = 'application/problem+json', $code = 0) {
    parent::__construct(Response::HTTP_NOT_ACCEPTABLE, $message, $previous, ['Content-Type' => $media_type], $code);
  }

}
