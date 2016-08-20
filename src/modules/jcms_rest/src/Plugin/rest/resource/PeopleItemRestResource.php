<?php

namespace Drupal\jcms_rest\Plugin\rest\resource;

use Drupal\image\Entity\ImageStyle;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "person_rest_resource",
 *   label = @Translation("Person rest resource"),
 *   uri_paths = {
 *     "canonical" = "/people/{id}"
 *   }
 * )
 */
class PeopleItemRestResource extends ResourceBase {
  /**
   * Responds to GET requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function get($id = NULL) {
    $query = \Drupal::entityQuery('node')
      ->condition('status', NODE_PUBLISHED)
      ->condition('changed', REQUEST_TIME, '<')
      ->condition('type', 'person')
      ->condition('field_person_id.value', $id);
    $status = Response::HTTP_OK;

    // @todo - elife - nlisgo - Handle version specific requests
    // @todo - elife - nlisgo - Handle content negotiation

    $nids = $query->execute();
    if ($nids) {
      $nid = reset($nids);
      /* @var \Drupal\node\Entity\Node $node */
      $node = \Drupal\node\Entity\Node::load($nid);
      $response = [
        'id' => $node->get('field_person_id')->first()->getValue()['value'],
        'type' => $node->get('field_person_type')->first()->getValue()['value'],
        'name' => [
          'preferred' => $node->getTitle(),
          'index' => $node->get('field_person_index_name')->first()->getValue()['value'],
        ],
      ];

      if ($node->get('field_image')->count()) {
        $response['image'] = [
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
        foreach ($response['image']['sizes'] as $ar => $sizes) {
          foreach ($sizes as $width => $height) {
            $image_style = [
              'crop',
              str_replace(':', 'x', $ar),
              $width . 'x' . $height,
            ];
            $response['image']['sizes'][$ar][$width] = ImageStyle::load(implode('_', $image_style))->buildUrl($image_uri);
          }
        }
      }

      $resource_response = new ResourceResponse($response, $status);
      // @todo - elife - nlisgo - Implement caching with options as a cacheable dependency, disable for now.
      $resource_response->addCacheableDependency(NULL);

      return $resource_response;
    }

    throw new NotFoundHttpException(t('Person with ID @id was not found', ['@id' => $id]));
  }

}
