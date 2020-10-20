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
 *   id = "collection_list_rest_resource",
 *   label = @Translation("Collection list rest resource"),
 *   uri_paths = {
 *     "canonical" = "/collections"
 *   }
 * )
 */
class CollectionListRestResource extends AbstractRestResourceBase {

  /**
   * Responds to GET requests.
   *
   * Returns a list of bundles for specified entity.
   */
  public function get() : JCMSRestResponse {
    $base_query = \Drupal::entityQuery('node')
      ->condition('type', 'collection');

    if (!$this->viewUnpublished()) {
      $base_query->condition('status', NodeInterface::PUBLISHED);
    }

    $this->filterSubjects($base_query);
    $this->filterContaining($base_query, 'field_collection_content');

    $count_query = clone $base_query;
    $items_query = clone $base_query;
    $response_data = [
      'total' => 0,
      'items' => [],
    ];
    $nodes = [];
    if ($total = $count_query->count()->execute()) {
      $response_data['total'] = (int) $total;
      $this->filterPageAndOrder($items_query, 'changed');
      $nids = $items_query->execute();
      $nodes = Node::loadMultiple($nids);
      if (!empty($nodes)) {
        foreach ($nodes as $node) {
          $response_data['items'][] = $this->getItem($node, 'thumbnail');
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
  public function getItem(EntityInterface $node, $image_size_types = ['banner', 'thumbnail']) : array {
    /* @var Node $node */
    $this->setSortBy('changed');
    $item = $this->processDefault($node);

    // Image is optional.
    if ($image = $this->processFieldImage($node->get('field_image'), FALSE, $image_size_types)) {
      $attribution = $this->fieldValueFormatted($node->get('field_image_attribution'), FALSE, TRUE);
      if (!empty($attribution)) {
        foreach ($image as $key => $type) {
          $image[$key]['attribution'] = $attribution;
        }
      }
      $item['image'] = $image;
    }

    // Social mage is optional.
    if ($socialImage = $this->processFieldImage($node->get('field_image_social'), FALSE, 'social', TRUE)) {
      $item['image'] = $item['image'] ?? [] + $socialImage;
    }

    // Impact statement is optional.
    if ($node->get('field_impact_statement')->count()) {
      $item['impactStatement'] = $this->fieldValueFormatted($node->get('field_impact_statement'));
      if (empty($item['impactStatement'])) {
        unset($item['impactStatement']);
      }
    }

    // Subjects is optional.
    $subjects = $this->processSubjects($node->get('field_subjects'));
    if (!empty($subjects)) {
      $item['subjects'] = $subjects;
    }

    $selectedCurator = $node->get('field_curators')->first()->get('entity')->getTarget()->getValue();
    $person_rest_resource = new PersonListRestResource([], 'person_list_rest_resource', [], $this->serializerFormats, $this->logger);
    $item['selectedCurator'] = $person_rest_resource->getItem($selectedCurator);
    if ($node->get('field_curators')->count() > 1) {
      $item['selectedCurator']['etAl'] = TRUE;
    }

    return $item;
  }

}
