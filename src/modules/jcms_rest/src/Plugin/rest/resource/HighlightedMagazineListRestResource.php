<?php

namespace Drupal\jcms_rest\Plugin\rest\resource;

use Drupal\entityqueue\Entity\EntitySubqueue;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "highlighted_magazine_list_rest_resource",
 *   label = @Translation("Highlighted magazine list rest resource"),
 *   uri_paths = {
 *     "canonical" = "/highlighted/magazine"
 *   }
 * )
 */
class HighlightedMagazineListRestResource extends AbstractRestResourceBase {

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

    foreach (EntitySubqueue::load('highlighted_magazine_articles')->get('items') as $item) {
      /* @var \Drupal\node\Entity\Node $item_node */
      $item_node = $item->get('entity')->getTarget()->getValue();
      // Unpublishing highlighted content doesn't seem to remove the items from the results.
      if ($item_node->isPublished()) {
        $response_data['items'][] = $this->getEntityQueueItem($item_node, $item_node->get('field_magazine_article'));
      }
    }

    $response_data = ['total' => count($response_data['items'])] + $response_data;

    $response = new JsonResponse($response_data, Response::HTTP_OK, ['Content-Type' => 'application/vnd.elife.highlighted-magazine-list+json;version=1']);
    return $response;
  }

}
