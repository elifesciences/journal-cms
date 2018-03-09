<?php

namespace Drupal\jcms_rest\Plugin\rest\resource;

use Drupal\Core\Entity\EntityInterface;
use Drupal\jcms_rest\Exception\JCMSBadRequestHttpException;
use Drupal\jcms_rest\Response\JCMSRestResponse;
use Drupal\node\Entity\Node;
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
   *
   * @todo - elife - nlisgo - Handle version specific requests
   */
  public function get() : JCMSRestResponse {
    $base_query = \Drupal::entityQuery('node')
      ->condition('status', NODE_PUBLISHED)
      ->condition('changed', REQUEST_TIME, '<')
      ->condition('type', 'collection');

    $this->filterSubjects($base_query);

    $containing = \Drupal::request()->query->get('containing', []);
    if (!empty($containing)) {
      $orCondition = $base_query->orConditionGroup();

      foreach ($containing as $item) {
        preg_match('~^(article|blog-article|interview)/([a-z0-9-]+)$~', $item, $matches);

        if (empty($matches[1]) || empty($matches[2])) {
          throw new JCMSBadRequestHttpException(t('Invalid containing parameter'));
        }

        $andCondition = $base_query->andConditionGroup()
          ->condition('field_collection_content.entity.type', str_replace('-', '_', $matches[1]));

        if ('article' === $matches[1]) {
          $andCondition = $andCondition->condition('field_collection_content.entity.title', $matches[2], '=');
        }
        else {
          $andCondition = $andCondition->condition('field_collection_content.entity.uuid', $matches[2], 'ENDS_WITH');
        }

        $orCondition = $orCondition->condition($andCondition);
      }

      $base_query = $base_query->condition($orCondition);
    }

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
    /* @var Node $node */
    $this->setSortBy('changed');
    $item = $this->processDefault($node);

    // Image is optional.
    if ($image = $this->processFieldImage($node->get('field_image'), FALSE, 'thumbnail')) {
      $attribution = $this->fieldValueFormatted($node->get('field_image_attribution'), FALSE, TRUE);
      if (!empty($attribution)) {
        foreach ($image as $key => $type) {
          $image[$key]['attribution'] = $attribution;
        }
      }
      $item['image'] = $image;
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
