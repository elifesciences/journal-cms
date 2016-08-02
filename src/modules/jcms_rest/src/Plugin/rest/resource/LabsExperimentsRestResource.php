<?php

namespace Drupal\jcms_rest\Plugin\rest\resource;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\image\Entity\ImageStyle;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
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
   */
  public function get() {
    $base_query = \Drupal::entityQuery('node')
      ->condition('status', NODE_PUBLISHED)
      ->condition('changed', REQUEST_TIME, '<')
      ->condition('type', 'labs_experiment');
    $status = Response::HTTP_OK;

    // @todo - elife - nlisgo - Handle version specific requests
    // @todo - elife - nlisgo - Handle content negotiation

    $count_query = clone $base_query;
    $items_query = clone $base_query;

    $response = [
      'total' => 0,
      'items' => [],
    ];
    $options = [];

    if ($total = $count_query->count()->execute()) {
      $response['total'] = (int) $total;

      $request = \Drupal::request();
      if ($page = $request->query->get('page')) {
        $options['page'] = $page;
      }

      if ($per_page = $request->query->get('per-page')) {
        $options['per-page'] = $per_page;
      }

      if ($order = $request->query->get('order')) {
        $options['order'] = $order;
      }

      $options += [
        'page' => 1,
        'per-page' => 20,
        'order' => 'desc',
      ];

      $items_query->range(($options['page']-1)*$options['per-page'], $options['per-page']);
      $items_query->sort('field_experiment_number.value', $options['order']);

      $nids = $items_query->execute();
      /* @var \Drupal\node\Entity\Node[] $nodes */
      $nodes = \Drupal\node\Entity\Node::loadMultiple($nids);
      foreach ($nodes as $node) {
        $item = [
          'number' => $node->get('field_experiment_number')->first()->getValue()['value'],
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

        $response['items'][] = $item;
      }
    }

    $resource_response = new ResourceResponse($response, $status);
    $cache_tags = explode('::', str_replace('=', ':', urldecode(http_build_query($options, '', '::'))));
    $resource_response->addCacheableDependency((new CacheableMetadata())->addCacheTags($cache_tags));

    return $resource_response;
  }

}
