<?php

namespace Drupal\jcms_rest\Plugin\rest\resource;

use Drupal\Core\Entity\EntityInterface;
use Drupal\jcms_rest\Exception\JCMSNotAcceptableHttpException;
use Drupal\jcms_rest\Exception\JCMSNotFoundHttpException;
use Drupal\jcms_rest\Response\JCMSRestResponse;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "collection_item_rest_resource",
 *   label = @Translation("Collection item rest resource"),
 *   uri_paths = {
 *     "canonical" = "/collections/{id}"
 *   }
 * )
 */
class CollectionItemRestResource extends AbstractRestResourceBase {
  protected $latestVersion = 2;

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
        ->condition('type', 'collection')
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

    throw new JCMSNotFoundHttpException(t('Collection with ID @id was not found', ['@id' => $id]));
  }

  /**
   * Takes a node and builds an item from it.
   */
  public function getItem(EntityInterface $node) : array {
    $collection_list_rest_resource = new CollectionListRestResource([], 'collection_list_rest_resource', [], $this->serializerFormats, $this->logger);
    $item = $collection_list_rest_resource->getItem($node);

    // Social image is optional.
    if ($socialImage = $this->processFieldImage($node->get('field_image_social'), FALSE, 'social', TRUE)) {
      $item['image']['social'] = $socialImage;
    }

    // Curators are required.
    $co = 0;
    $people_rest_resource = new PersonListRestResource([], 'person_list_rest_resource', [], $this->serializerFormats, $this->logger);
    $item['curators'] = [];
    foreach ($node->get('field_curators')->referencedEntities() as $curator) {
      /* @var Node $curator */
      if ($curator->isPublished() || $this->viewUnpublished()) {
        $curator_item = $people_rest_resource->getItem($curator);
        $item['curators'][] = $curator_item;
        if ($co === 0) {
          $item['selectedCurator'] = $curator_item;
        }
        elseif ($co === 1) {
          $item['selectedCurator']['etAl'] = TRUE;
        }
        $co++;
      }
    }

    $item += $this->extendedCollectionItem($node);

    foreach (['field_collection_content', 'field_collection_related_content'] as $field) {
      foreach ($node->get($field)->referencedEntities() as $content) {
        /* @var Node $content */
        if ($content->isPublished() || $this->viewUnpublished()) {
          switch ($content->getType()) {
            case 'event':
              if ($this->acceptVersion < 2) {
                throw new JCMSNotAcceptableHttpException('This collection requires version 2+.');
              }
              break;

            case 'digest':
              if ($snippet = $this->getDigestSnippet($content)) {
                if ($this->acceptVersion < 2) {
                  throw new JCMSNotAcceptableHttpException('This collection requires version 2+.');
                }
              }

            default:
          }
        }
      }
    }

    return $item;
  }

}
