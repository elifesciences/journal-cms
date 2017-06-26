<?php

namespace Drupal\jcms_rest\Plugin\rest\resource;

use Drupal\jcms_rest\Exception\JCMSNotFoundHttpException;
use Drupal\jcms_rest\Response\JCMSRestResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "labs_experiment_item_rest_resource",
 *   label = @Translation("Labs post item rest resource"),
 *   uri_paths = {
 *     "canonical" = "/labs-posts/{number}"
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
  public function get($id) {
    $query = \Drupal::entityQuery('node')
      ->condition('status', NODE_PUBLISHED)
      ->condition('changed', REQUEST_TIME, '<')
      ->condition('type', 'labs_experiment')
      ->condition('uuid', '%' . $id, 'LIKE');

    $nids = $query->execute();
    if ($nids) {
      $nid = reset($nids);
      /* @var \Drupal\node\Entity\Node $node */
      $node = \Drupal\node\Entity\Node::load($nid);

      $this->setSortBy('created', TRUE);
      $response = $this->processDefault($node);

      // Image is required.
      $response['image'] = $this->processFieldImage($node->get('field_image'), TRUE);

      // Impact statement is optional.
      if ($node->get('field_impact_statement')->count()) {
        $response['impactStatement'] = $this->fieldValueFormatted($node->get('field_impact_statement'));
        if (empty($response['impactStatement'])) {
          unset($response['impactStatement']);
        }
      }

      if ($content = $this->processFieldContent($node->get('field_content'))) {
        $response['content'] = $content;
      }

      $response = new JCMSRestResponse($response, Response::HTTP_OK, ['Content-Type' => $this->getContentType()]);
      $response->addCacheableDependency($node);
      return $response;
    }

    throw new JCMSNotFoundHttpException(t('Lab experiment with ID @id was not found', ['@id' => $id]));
  }

}
