<?php

namespace Drupal\jcms_rest\Plugin\rest\resource;

use Drupal\jcms_rest\Exception\JCMSNotFoundHttpException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "labs_experiment_item_rest_resource",
 *   label = @Translation("Labs experiment item rest resource"),
 *   uri_paths = {
 *     "canonical" = "/labs-experiments/{number}"
 *   }
 * )
 */
class LabsExperimentItemRestResource extends AbstractRestResourceBase {
  /**
   * Responds to GET requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @param int $number
   * @return array|\Symfony\Component\HttpFoundation\JsonResponse
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function get(int $number) {
    $query = \Drupal::entityQuery('node')
      ->condition('status', NODE_PUBLISHED)
      ->condition('changed', REQUEST_TIME, '<')
      ->condition('type', 'labs_experiment')
      ->condition('field_experiment_number.value', $number);

    $nids = $query->execute();
    if ($nids) {
      $nid = reset($nids);
      /* @var \Drupal\node\Entity\Node $node */
      $node = \Drupal\node\Entity\Node::load($nid);

      $this->setSortBy('created', TRUE);
      $response = $this->processDefault($node, $number, 'number');

      // Image is required.
      $response['image'] = $this->processFieldImage($node->get('field_image'), TRUE);

      // Impact statement is optional.
      if ($node->get('field_impact_statement')->count()) {
        $response['impactStatement'] = $this->fieldValueFormatted($node->get('field_impact_statement'));
      }

      if ($content = $this->processFieldContent($node->get('field_content'))) {
        $response['content'] = $content;
      }

      $response = new JsonResponse($response, Response::HTTP_OK, ['Content-Type' => 'application/vnd.elife.labs-experiment+json;version=1']);
      return $response;
    }

    throw new JCMSNotFoundHttpException(t('Lab experiment with ID @id was not found', ['@id' => $number]));
  }

}
