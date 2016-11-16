<?php

namespace Drupal\jcms_rest\Plugin\rest\resource;

use Drupal\Core\Entity\EntityInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "interview_list_rest_resource",
 *   label = @Translation("Interview list rest resource"),
 *   uri_paths = {
 *     "canonical" = "/interviews"
 *   }
 * )
 */
class InterviewListRestResource extends AbstractRestResourceBase {

  /**
   * Responds to GET requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   *
   * @todo - elife - nlisgo - Handle version specific requests
   */
  public function get() {
    $base_query = \Drupal::entityQuery('node')
      ->condition('status', NODE_PUBLISHED)
      ->condition('changed', REQUEST_TIME, '<')
      ->condition('type', 'interview');

    $count_query = clone $base_query;
    $items_query = clone $base_query;
    $response_data = [
      'total' => 0,
      'items' => [],
    ];
    if ($total = $count_query->count()->execute()) {
      $response_data['total'] = (int) $total;
      $this->filterPageAndOrder($items_query);
      $nids = $items_query->execute();
      $nodes = Node::loadMultiple($nids);
      if (!empty($nodes)) {
        foreach ($nodes as $node) {
          $response_data['items'][] = $this->getItem($node);
        }
      }
    }
    $response = new JsonResponse($response_data, Response::HTTP_OK, ['Content-Type' => 'application/vnd.elife.interview-list+json;version=1']);
    return $response;
  }

  /**
   * Takes a node and builds an item from it.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node
   *
   * @return array
   */
  public function getItem(EntityInterface $node) {
    /* @var Node $node */
    $item = $this->processDefault($node);

    $item['interviewee']['name'] = [
      'preferred' => $node->get('field_person_preferred_name')->getString(),
      'index' => $node->get('field_person_index_name')->getString(),
    ];

    // Impact statement is optional.
    if ($node->get('field_impact_statement')->count()) {
      $item['impactStatement'] = $this->fieldValueFormatted($node->get('field_impact_statement'));
    }

    return $item;
  }

}
