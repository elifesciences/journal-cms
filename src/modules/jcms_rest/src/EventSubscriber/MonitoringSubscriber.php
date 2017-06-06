<?php
namespace Drupal\jcms_rest\EventSubscriber;

use Drupal\Core\Utility\Error;
use eLife\Logging\Monitoring;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Records exceptions for monitoring.
 */
class MonitoringSubscriber implements EventSubscriberInterface {

  /**
   * @var \eLife\Logging\Monitoring
   */
  private $monitoring;

  public function __construct(Monitoring $monitoring) {
    $this->monitoring = $monitoring;
  }

  /**
   * Log all exceptions.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  public function onException(GetResponseForExceptionEvent $event) {
    $exception = $event->getException();

    $this->monitoring->recordException($exception);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::EXCEPTION][] = ['onException', 50];
    return $events;
  }
}
