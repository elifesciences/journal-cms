<?php

namespace Drupal\jcms_rest\Plugin\rest\resource;

use Drupal\entityqueue\Entity\EntitySubqueue;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "cover_current_list_rest_resource",
 *   label = @Translation("Cover current list rest resource"),
 *   uri_paths = {
 *     "canonical" = "/covers/current"
 *   }
 * )
 */
class CoverCurrentListRestResource extends AbstractRestResourceBase {

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
    $response_data = [
      'items' => [],
    ];

    $cover_rest_resource = new CoverListRestResource([], 'cover_list_rest_resource', [], $this->serializerFormats, $this->logger);
    foreach (EntitySubqueue::load('covers')->get('items') as $item) {
      /* @var \Drupal\node\Entity\Node $item_node */
      $item_node = $item->get('entity')->getTarget()->getValue();
      // @todo - elife - nlisgo - some of the migrated covers did not retain images.
      if ($item_node->isPublished() && $item_node->get('field_image')->count()) {
        $response_data['items'][] = $cover_rest_resource->getItem($item_node);
      }
    }

    $response_data = ['total' => count($response_data['items'])] + $response_data;

    $response = new JsonResponse($response_data, Response::HTTP_OK, ['Content-Type' => 'application/vnd.elife.cover-list+json;version=1']);
    return $response;
  }

}
