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
   * @todo - elife - nlisgo - some of the migrated covers did not retain images.
   */
  public function get() {
    if ($this->viewUnpublished()) {
      $response_data = $this->getPreview();
    }
    else {
      $response_data = $this->getPublished();
    }

    $response = new JsonResponse($response_data, Response::HTTP_OK, ['Content-Type' => $this->getContentType()]);
    return $response;
  }

  /**
   * Get the current covers list.
   *
   * @return array
   */
  public function getPublished() {
    $response_data = [
      'items' => [],
    ];

    $cover_rest_resource = new CoverListRestResource([], 'cover_list_rest_resource', [], $this->serializerFormats, $this->logger);
    foreach (EntitySubqueue::load('covers')->get('items') as $item) {
      /* @var \Drupal\node\Entity\Node $item_node */
      $item_node = $item->get('entity')->getTarget()->getValue();
      if ($item_node->isPublished() && $item_node->get('field_image')->count()) {
        $response_data['items'][] = $cover_rest_resource->getItem($item_node);
      }
    }

    return ['total' => count($response_data['items'])] + $response_data;
  }

  /**
   * Get the current covers preview list.
   *
   * @return array
   */
  public function getPreview() {
    $response_data = [
      'items' => [],
    ];

    $cover_rest_resource = new CoverListRestResource([], 'cover_list_rest_resource', [], $this->serializerFormats, $this->logger);
    $subqueue = EntitySubqueue::load('covers_preview');
    $items = $subqueue->get('items');
    $limit = (int) $subqueue->get('field_covers_active_items')->getString();
    foreach ($items as $item) {
      $limit--;
      /* @var \Drupal\node\Entity\Node $item_node */
      $item_node = $item->get('entity')->getTarget()->getValue();
      $moderation_info = \Drupal::service('content_moderation.moderation_information');
      if (!$moderation_info->isLatestRevision($item_node)) {
        $item_node = $moderation_info->getLatestRevision($item_node->getEntityTypeId(), $item_node->id());
      }
      if ($item_node->get('field_image')->count()) {
        $response_data['items'][] = $cover_rest_resource->getItem($item_node);
      }

      if ($limit <= 0) {
        break;
      }
    }

    return ['total' => count($response_data['items'])] + $response_data;
  }

}
