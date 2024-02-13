<?php

namespace Drupal\jcms_rest\Plugin\rest\resource;

use Drupal\entityqueue\Entity\EntitySubqueue;
use Drupal\jcms_rest\Response\JCMSRestResponse;
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
   * Cover nodes.
   *
   * @var \Drupal\node\Entity\Node[]
   */
  private $nodes = [];

  /**
   * Responds to GET requests.
   *
   * Returns a list of bundles for specified entity.
   */
  public function get() : JCMSRestResponse {
    if ($this->viewUnpublished()) {
      $response_data = $this->getPreview();
    }
    else {
      $response_data = $this->getPublished();
    }

    $response = new JCMSRestResponse($response_data, Response::HTTP_OK, ['Content-Type' => $this->getContentType()]);
    $response->addCacheableDependencies($this->nodes);
    $this->processResponse($response);
    return $response;
  }

  /**
   * Get the current covers list.
   */
  public function getPublished() : array {
    $response_data = [
      'items' => [],
    ];

    $cover_rest_resource = new CoverListRestResource([], 'cover_list_rest_resource', [], $this->serializerFormats, $this->logger);
    foreach (EntitySubqueue::load('covers')->get('items') as $item) {
      /** @var \Drupal\node\Entity\Node $item_node */
      $item_node = $item->get('entity')->getTarget()->getValue();
      if ($item_node->isPublished() && $item_node->get('field_image')->count()) {
        if ($item = $cover_rest_resource->getItem($item_node)) {
          $this->nodes[$item_node->id()] = $item_node;
          $response_data['items'][] = $item;
        }
      }
    }

    return ['total' => count($response_data['items'])] + $response_data;
  }

  /**
   * Get the current covers preview list.
   */
  public function getPreview() : array {
    $response_data = [
      'items' => [],
    ];

    $cover_rest_resource = new CoverListRestResource([], 'cover_list_rest_resource', [], $this->serializerFormats, $this->logger);
    $subqueue = EntitySubqueue::load('covers_preview');
    $items = $subqueue->get('items');
    $limit = 4;
    foreach ($items as $item) {
      $limit--;
      /** @var \Drupal\node\Entity\Node $item_node */
      $item_node = $item->get('entity')->getTarget()->getValue();
      if (!$item_node->isLatestRevision()) {
        /** @var \Drupal\Core\Entity\RevisionableStorageInterface $entity_storage */
        $entity_storage = \Drupal::entityTypeManager()
          ->getStorage($item_node->getEntityTypeId());
        $latest_revision_id = $entity_storage->getLatestRevisionId($item_node->id());
        $item_node = $entity_storage->loadRevision($latest_revision_id);
      }
      if ($item_node->get('field_image')->count()) {
        if ($item = $cover_rest_resource->getItem($item_node)) {
          $this->nodes[$item_node->id()] = $item_node;
          $response_data['items'][] = $item;
        }
      }

      if ($limit <= 0) {
        break;
      }
    }

    return ['total' => count($response_data['items'])] + $response_data;
  }

}
