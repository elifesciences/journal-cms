<?php

namespace Drupal\jcms_rest\Plugin\rest\resource;

use Drupal\Core\Entity\EntityInterface;
use Drupal\jcms_rest\Response\JCMSRestResponse;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "job_advert_list_rest_resource",
 *   label = @Translation("Job advert list rest resource"),
 *   uri_paths = {
 *     "canonical" = "/job-adverts"
 *   }
 * )
 */
class JobAdvertListRestResource extends AbstractRestResourceBase {

  /**
   * Responds to GET requests.
   *
   * Returns a list of bundles for specified entity.
   */
  public function get() : JCMSRestResponse {
    $base_query = \Drupal::entityQuery('node')
      ->accessCheck(TRUE)
      ->condition('type', 'job_advert');

    if (!$this->viewUnpublished()) {
      $base_query->condition('status', NodeInterface::PUBLISHED);
    }

    $this->filterShow($base_query, 'field_job_advert_closing_date', TRUE);
    $count_query = clone $base_query;
    $items_query = clone $base_query;
    $response_data = [
      'total' => 0,
      'items' => [],
    ];
    $nodes = [];
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
    $response = new JCMSRestResponse($response_data, Response::HTTP_OK, ['Content-Type' => $this->getContentType()]);
    $response->addCacheableDependencies($nodes);
    $this->processResponse($response);
    return $response;
  }

  /**
   * Takes a node and builds an item from it.
   */
  public function getItem(EntityInterface $node) : array {
    /** @var \Drupal\node\Entity\Node $node */
    $item = $this->processDefault($node);

    // Impact statement is optional.
    if ($node->get('field_impact_statement')->count()) {
      $item['impactStatement'] = $this->fieldValueFormatted($node->get('field_impact_statement'));
      if (empty($item['impactStatement'])) {
        unset($item['impactStatement']);
      }
    }

    $item['closingDate'] = $this->formatDate($node->get('field_job_advert_closing_date')->first()->getValue()['value']);
    return $item;
  }

}
