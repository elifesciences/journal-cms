<?php

namespace Drupal\jcms_rest\Plugin\rest\resource;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jcms_rest\Response\JCMSRestResponse;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
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
   * @todo - elife - nlisgo - Handle version specific requests
   */
  public function get() : JCMSRestResponse {
    $base_query = \Drupal::entityQuery('node')
      ->condition('changed', \Drupal::time()->getRequestTime(), '<')
      ->condition('type', 'cover')
      ->exists('field_image');

    if (!$this->viewUnpublished()) {
      $base_query->condition('status', NodeInterface::PUBLISHED);
    }

    $this->filterSubjects($base_query);
    $this->filterDateRange($base_query, 'field_cover_content.entity.field_order_date.value', 'field_cover_content.entity.created');

    $count_query = clone $base_query;
    $items_query = clone $base_query;
    $response_data = [
      'total' => 0,
      'items' => [],
    ];
    $nodes = [];
    if ($total = $count_query->count()->execute()) {
      $response_data['total'] = (int) $total;

      $sort_by = ($this->getRequestOption('sort') == 'page-views') ? 'field_cover_content.entity.field_page_views.value' : ['field_cover_content.entity.created', 'field_cover_content.entity.field_page_views.value'];
      $this->filterPageAndOrder($items_query, $sort_by);
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
    $response = new JCMSRestResponse($response_data, Response::HTTP_OK, ['Content-Type' => $this->getContentType()]);
    $response->addCacheableDependencies($nodes);
    $this->processResponse($response);
    return $response;
  }

  /**
   * Takes a node and builds an item from it.
   */
  public function getItem(EntityInterface $node) : array {
    return $this->getEntityQueueItem($node, $node->get('field_cover_content'));
  }

  /**
   * Apply filter for subjects by amending query.
   */
  protected function filterSubjects(QueryInterface &$query) {
    $subjects = $this->getRequestOption('subject');
    if (!empty($subjects)) {
      $query->condition('field_cover_content.entity.field_subjects.entity.field_subject_id.value', $subjects, 'IN');
    }
  }

}
