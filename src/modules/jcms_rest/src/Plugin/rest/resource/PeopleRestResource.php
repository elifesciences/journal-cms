<?php

namespace Drupal\jcms_rest\Plugin\rest\resource;

use Drupal\image\Entity\ImageStyle;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "person_list_rest_resource",
 *   label = @Translation("Person list rest resource"),
 *   uri_paths = {
 *     "canonical" = "/people"
 *   }
 * )
 */
class PeopleRestResource extends ResourceBase {
  /**
   * Responds to GET requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function get() {
    $base_query = \Drupal::entityQuery('node')
      ->condition('status', NODE_PUBLISHED)
      ->condition('changed', REQUEST_TIME, '<')
      ->condition('type', 'person');
    $status = Response::HTTP_OK;

    // @todo - elife - nlisgo - Handle version specific requests
    // @todo - elife - nlisgo - Handle content negotiation

    $request = \Drupal::request();
    $options = [
      'page' => $request->query->get('page', 1),
      'per-page' => $request->query->get('per-page', 20),
      'order' => $request->query->get('order', 'desc'),
      'subject' => (array) $request->query->get('subject', []),
    ];

    if (!empty($options['subject'])) {
      // @todo - elife - nlisgo filter by those that have the research expertise.
    }

    $count_query = clone $base_query;
    $items_query = clone $base_query;

    $response = [
      'total' => 0,
      'items' => [],
    ];

    if ($total = $count_query->count()->execute()) {
      $response['total'] = (int) $total;

      $items_query->range(($options['page']-1)*$options['per-page'], $options['per-page']);
      $items_query->sort('field_episode_number.value', $options['order']);

      // @todo - elife - nlisgo - filter by subject

      $nids = $items_query->execute();
      /* @var \Drupal\node\Entity\Node[] $nodes */
      $nodes = \Drupal\node\Entity\Node::loadMultiple($nids);
      foreach ($nodes as $node) {
        $item = [
          'id' => $node->get('field_person_id')->first()->getValue()['value'],
          'type' => $node->get('field_person_type')->first()->getValue()['value'],
          'name' => [
            'preferred' => $node->getTitle(),
            'index' => $node->get('field_person_index_name')->first()->getValue()['value'],
          ],
        ];

        if ($node->get('field_image')->count()) {
          $item['image'] = [
            'alt' => $node->get('field_image')->first()->getValue()['alt'],
            'sizes' => [
              '16:9' => [
                250 => '141',
                500 => '282',
              ],
              '1:1' => [
                70 => '70',
                140 => '140',
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
        }

        $response['items'][] = $item;
      }
    }

    $resource_response = new ResourceResponse($response, $status);
    // @todo - elife - nlisgo - Implement caching with options as a cacheable dependency, disable for now.
    $resource_response->addCacheableDependency(NULL);

    return $resource_response;
  }

}
