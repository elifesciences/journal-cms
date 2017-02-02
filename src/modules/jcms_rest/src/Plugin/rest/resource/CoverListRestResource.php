<?php

namespace Drupal\jcms_rest\Plugin\rest\resource;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "cover_list_rest_resource",
 *   label = @Translation("Cover list rest resource"),
 *   uri_paths = {
 *     "canonical" = "/covers"
 *   }
 * )
 */
class CoverListRestResource extends AbstractRestResourceBase {

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
      ->condition('type', 'cover')
      // @todo - elife - nlisgo - check why some legacy images aren't migrating.
      ->exists('field_image');

    $this->filterSubjects($base_query);
    $this->filterDateRange($base_query, 'field_cover_content.entity.created');

    $count_query = clone $base_query;
    $items_query = clone $base_query;
    $response_data = [
      'total' => 0,
      'items' => [],
    ];
    if ($total = $count_query->count()->execute()) {
      $response_data['total'] = (int) $total;
      $this->filterPageAndOrder($items_query, 'field_cover_content.entity.created');
      $nids = $items_query->execute();
      $nodes = Node::loadMultiple($nids);
      if (!empty($nodes)) {
        foreach ($nodes as $node) {
          if ($item = $this->getItem($node)) {
            $response_data['items'][] = $item;
          }
        }
      }
    }
    $response = new JsonResponse($response_data, Response::HTTP_OK, ['Content-Type' => 'application/vnd.elife.cover-list+json;version=1']);
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
    return $this->getEntityQueueItem($node, $node->get('field_cover_content'));
  }

  /**
   * Apply filter for subjects by amending query.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   */
  protected function filterSubjects(QueryInterface &$query) {
    $subjects = $this->getRequestOption('subject');
    if (!empty($subjects)) {
      $query->condition('field_cover_content.entity.field_subjects.entity.field_subject_id.value', $subjects, 'IN');
    }
  }

}
