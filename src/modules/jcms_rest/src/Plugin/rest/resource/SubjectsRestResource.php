<?php

namespace Drupal\jcms_rest\Plugin\rest\resource;

use Drupal\Core\Entity\EntityInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\image\Entity\ImageStyle;
use Drupal\taxonomy\Entity\Term;
use Drupal\rest\ResourceResponse;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "subjects_list_rest_resource",
 *   label = @Translation("Subjects list rest resource"),
 *   uri_paths = {
 *     "canonical" = "/subjects"
 *   }
 * )
 */
class SubjectsRestResource extends ResourceBase {

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
    $base_query = \Drupal::entityQuery('taxonomy_term')
      ->condition('vid', 'subjects');
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
      $items_query->sort('name', $request_options['order']);
      $tids = $items_query->execute();
      $terms = Term::loadMultiple($tids);
      if (!empty($terms)) {
        foreach ($terms as $term) {
          $response_data['items'][] = $this->getItem($term);
        }
      }
    }
    $response = new ResourceResponse($response_data);
    // @todo - Digirati - jamiehollern - Alter response content type header to include the API version.
    // @todo - elife - nlisgo - Implement caching with options as a cacheable dependency, disable for now.
    $response->addCacheableDependency(NULL);
    //$response->addCacheableDependency($version); // ???
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
   * Takes a taxonomy term and builds an item from it.
   *
   * @param \Drupal\taxonomy\Entity\Term $term
   *
   * @return array
   */
  public function getItem(EntityInterface $term) {
    $item = [
      'id' => $term->get('field_subject_id')->first()->getValue()['value'],
      'name' => $term->toLink()->getText(),
      'image' => [
        'alt' => $term->get('field_image')->first()->getValue()['alt'],
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
    $image_uri = $term->get('field_image')->first()->get('entity')->getTarget()->get('uri')->first()->getValue()['value'];
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

    if ($term->get('field_impact_statement')->count()) {
      $item['impactStatement'] = $term->get('field_impact_statement')->first()->getValue()['value'];
    }
    return $item;
  }

}
