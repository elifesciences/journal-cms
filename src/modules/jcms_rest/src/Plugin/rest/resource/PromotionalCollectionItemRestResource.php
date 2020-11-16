<?php

namespace Drupal\jcms_rest\Plugin\rest\resource;

use Drupal\Core\Entity\EntityInterface;
use Drupal\jcms_rest\Exception\JCMSNotFoundHttpException;
use Drupal\jcms_rest\Response\JCMSRestResponse;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "promotional_collection_item_rest_resource",
 *   label = @Translation("Promotional collection item rest resource"),
 *   uri_paths = {
 *     "canonical" = "/promotional-collections/{id}"
 *   }
 * )
 */
class PromotionalCollectionItemRestResource extends AbstractRestResourceBase {

  /**
   * Responds to GET requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @throws JCMSNotFoundHttpException
   */
  public function get(string $id) : JCMSRestResponse {
    if ($this->checkId($id)) {
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'promotional_collection')
        ->condition('uuid', '%' . $id, 'LIKE');

      if (!$this->viewUnpublished()) {
        $query->condition('status', NodeInterface::PUBLISHED);
      }

      $nids = $query->execute();
      if ($nids) {
        $nid = reset($nids);
        $node = Node::load($nid);
        $item = $this->getItem($node);
        $response = new JCMSRestResponse($item, Response::HTTP_OK, ['Content-Type' => $this->getContentType()]);
        $response->addCacheableDependency($node);
        $this->processResponse($response);
        return $response;
      }
    }

    throw new JCMSNotFoundHttpException(t('Promotional collection with ID @id was not found', ['@id' => $id]));
  }

  /**
   * Takes a node and builds an item from it.
   */
  public function getItem(EntityInterface $node) : array {
    $promotional_collection_list_rest_resource = new PromotionalCollectionListRestResource([], 'promotional_collection_list_rest_resource', [], $this->serializerFormats, $this->logger);
    $item = $promotional_collection_list_rest_resource->getItem($node);

    // Social image is optional.
    if ($socialImage = $this->processFieldImage($node->get('field_image_social'), FALSE, 'social', TRUE)) {
      $item['image']['social'] = $socialImage;
    }

    // Editors are optional.
    if ($node->get('field_editors')->count()) {
      $people_rest_resource = new PersonListRestResource([], 'person_list_rest_resource', [], $this->serializerFormats, $this->logger);
      $item['editors'] = [];
      foreach ($node->get('field_editors')->referencedEntities() as $editor) {
        /* @var Node $editor */
        if ($editor->isPublished() || $this->viewUnpublished()) {
          $item['editors'][] = $people_rest_resource->getItem($editor);
        }
      }
    }

    return $item + $this->extendedCollectionItem($node);
  }

}
