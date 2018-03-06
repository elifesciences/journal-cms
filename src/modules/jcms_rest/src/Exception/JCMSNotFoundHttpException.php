<?php

namespace Drupal\jcms_rest\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * This should be used instead of the Symfony NotFoundHttpException.
 *
 * We need this to set the content type header.
 *
 * @package Drupal\jcms_rest\Exception
 */
class JCMSNotFoundHttpException extends HttpException {

  /**
   * JCMSNotFoundHttpException constructor.
   */
  public function __construct($message, \Exception $previous = NULL, $media_type = 'application/problem+json', $code = 0) {
    parent::__construct(Response::HTTP_NOT_FOUND, $message, $previous, ['Content-Type' => $media_type], $code);
  }

}
