<?php

namespace Drupal\jcms_rest\Plugin\rest\resource;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\entityqueue\Entity\EntitySubqueue;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "highlighted_podcast_list_rest_resource",
 *   label = @Translation("Highlighted podcast list rest resource"),
 *   uri_paths = {
 *     "canonical" = "/highlighted/podcast"
 *   }
 * )
 */
class HighlightedPodcastListRestResource extends AbstractRestResourceBase {

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

    foreach (EntitySubqueue::load('highlighted_podcast_chapters')->get('items') as $item) {
      /* @var \Drupal\node\Entity\Node $item_node */
      $item_node = $item->get('entity')->getTarget()->getValue();
      // Unpublishing highlighted content doesn't seem to remove the items from the results.
      if ($item_node->isPublished()) {
        if ($item = $this->getEntityQueueItem($item_node, $item_node->get('field_podcast_chapter'))) {
          $response_data['items'][] = $item;
        }
      }
    }

    $response_data = ['total' => count($response_data['items'])] + $response_data;

    $response = new JsonResponse($response_data, Response::HTTP_OK, ['Content-Type' => 'application/vnd.elife.highlighted-podcast-list+json;version=1']);
    return $response;
  }

  /**
   * Takes a node and builds an item from it.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node
   * @param \Drupal\Core\Field\FieldItemListInterface $related_field
   *
   * @return array
   */
  public function getEntityQueueItem(EntityInterface $node, FieldItemListInterface $related_field) {
    /* @var Node $node */
    /* @var Node $related */
    $related = $related_field->first()->get('entity')->getTarget()->getValue();
    $podcast_episode_item = new PodcastEpisodeItemRestResource([], 'podcast_episode_item_rest_resource', [], $this->serializerFormats, $this->logger);

    $item_values = [
      'title' => $node->getTitle(),
      'image' => $this->processFieldImage($node->get('field_image'), TRUE),
    ];

    return $item_values + $podcast_episode_item->getChapterItem($related, 0);
  }

}
