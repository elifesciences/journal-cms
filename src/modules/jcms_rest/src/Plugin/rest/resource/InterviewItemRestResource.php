<?php

namespace Drupal\jcms_rest\Plugin\rest\resource;

use Drupal\jcms_rest\Exception\JCMSNotFoundHttpException;
use Drupal\jcms_rest\Response\JCMSRestResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "interview_item_rest_resource",
 *   label = @Translation("Interview item rest resource"),
 *   uri_paths = {
 *     "canonical" = "/interviews/{id}"
 *   }
 * )
 */
class InterviewItemRestResource extends AbstractRestResourceBase {
  /**
   * Responds to GET requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @param string $id
   * @return array|\Symfony\Component\HttpFoundation\JsonResponse
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function get($id) {
    $query = \Drupal::entityQuery('node')
      ->condition('status', NODE_PUBLISHED)
      ->condition('changed', REQUEST_TIME, '<')
      ->condition('type', 'interview')
      ->condition('uuid', '%' . $id, 'LIKE');

    $nids = $query->execute();
    if ($nids) {
      $nid = reset($nids);
      /* @var \Drupal\node\Entity\Node $node */
      $node = \Drupal\node\Entity\Node::load($nid);

      $response = $this->processDefault($node, $id);

      $response['interviewee']['name'] = [
        'preferred' => $node->get('field_person_preferred_name')->getString(),
        'index' => $node->get('field_person_index_name')->getString(),
      ];

      if ($node->get('field_interview_cv')->count()) {
        $response['interviewee']['cv'] = [];
        foreach ($node->get('field_interview_cv') as $paragraph) {
          $cv_item = $paragraph->get('entity')->getTarget()->getValue();
          $response['interviewee']['cv'][] = [
            'date' => $cv_item->get('field_cv_item_date')->getString(),
            'text' => $cv_item->get('field_block_html')->getString(),
          ];
        }
      }

      // Impact statement is optional.
      if ($node->get('field_impact_statement')->count()) {
        $response['impactStatement'] = $this->fieldValueFormatted($node->get('field_impact_statement'));
      }

      if ($content = $this->processFieldContent($node->get('field_content'))) {
        $response['content'] = $content;
      }

      $response = new JCMSRestResponse($response, Response::HTTP_OK, ['Content-Type' => $this->getContentType()]);
      $response->addCacheableDependency($node);
      return $response;
    }

    throw new JCMSNotFoundHttpException(t('Interview with ID @id was not found', ['@id' => $id]));
  }

}
