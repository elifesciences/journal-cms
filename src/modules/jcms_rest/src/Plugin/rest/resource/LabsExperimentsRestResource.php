<?php

namespace Drupal\jcms_rest\Plugin\rest\resource;

use Drupal\Core\Entity\EntityInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\node\Entity\Node;
use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "labs_experiments_list_rest_resource",
 *   label = @Translation("Labs experiments list rest resource"),
 *   uri_paths = {
 *     "canonical" = "/labs-experiments"
 *   }
 * )
 */
class LabsExperimentsRestResource extends ResourceBase {

  /**
   * Responds to GET requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   *
   * @todo - elife - nlisgo - Handle version specific requests
   * @todo - elife - nlisgo - Handle content negotiation
   */
  public function get() {
    $base_query = \Drupal::entityQuery('node')
      ->condition('status', NODE_PUBLISHED)
      ->condition('changed', REQUEST_TIME, '<')
      ->condition('type', 'labs_experiment');
    $count_query = clone $base_query;
    $items_query = clone $base_query;
    $response_data = [
      'total' => 0,
      'items' => [],
    ];
    if ($total = $count_query->count()->execute()) {
      $response_data['total'] = (int) $total;
      $request_options = $this->getRequestOptions();
      $items_query->range(($request_options['page'] - 1) * $request_options['per-page'], $request_options['per-page']);
      $items_query->sort('field_experiment_number.value', $request_options['order']);
      $nids = $items_query->execute();
      $nodes = Node::loadMultiple($nids);
      if (!empty($nodes)) {
        foreach ($nodes as $node) {
          $response_data['items'][] = $this->getItem($node);
        }
      }
    }
    $response = new JsonResponse($response_data, Response::HTTP_OK, ['Content-Type' => 'application/vnd.elife.labs-experiment-list+json;version=1']);
    return $response;
  }

  /**
   * Returns an array of Drupal request options.
   *
   * @return array
   */
  public function getRequestOptions() {
    $request = \Drupal::request();
    $options = [
      'page' => $request->query->get('page', 1),
      'per-page' => $request->query->get('per-page', 20),
      'order' => $request->query->get('order', 'desc'),
    ];
    return $options;
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
    $item = [
      'number' => (int) $node->get('field_experiment_number')->first()->getValue()['value'],
      'title' => $node->getTitle(),
      'published' => \Drupal::service('date.formatter')->format($node->getCreatedTime(), 'html_datetime'),
      'image' => [
        'alt' => $node->get('field_image')->first()->getValue()['alt'],
        'sizes' => [
          '2:1' => [
            900 => '450',
            1800 => '900',
          ],
          '16:9' => [
            250 => '141',
            500 => '282',
          ],
          '1:1' => [
            70 => '70',
            140 => '140',
          ],
        ],
      ],
    ];
    $image_uri = $node->get('field_image')->first()->get('entity')->getTarget()->get('uri')->first()->getValue()['value'];
    foreach ($item['image']['sizes'] as $ar => $sizes) {
      foreach ($sizes as $width => $height) {
        $image_style = [
          'crop',
          str_replace(':', 'x', $ar),
          $width . 'x' . $height,
        ];
        $item['image']['sizes'][$ar][$width] = ImageStyle::load(implode('_', $image_style))->buildUrl($image_uri);
      }
    }

    if ($node->get('field_impact_statement')->count()) {
      $item['impactStatement'] = $node->get('field_impact_statement')->first()->getValue()['value'];
    }
    return $item;
  }

}
