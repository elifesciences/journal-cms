<?php

namespace Drupal\labs_experiment\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "labs_experiment_rest_resource",
 *   label = @Translation("Labs experiment rest resource"),
 *   uri_paths = {
 *     "canonical" = "/labs-experiments/{number}"
 *   }
 * )
 */
class LabsExperimentRestResource extends ResourceBase {
  /**
   * Responds to GET requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function get($number = NULL) {
    $query = \Drupal::entityQuery('node')
      ->condition('status', 1)
      ->condition('changed', REQUEST_TIME, '<')
      ->condition('type', 'labs_experiment')
      ->condition('field_experiment_number.value', $number);

    $nids = $query->execute();
    if ($nids) {
      $nid = reset($nids);
      $node = \Drupal\node\Entity\Node::load($nid);
      return new ResourceResponse($node);
    }

    throw new NotFoundHttpException(t('Lab experiment with ID @id was not found', ['@id' => $number]));
  }

}
