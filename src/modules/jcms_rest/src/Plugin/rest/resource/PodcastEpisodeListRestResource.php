<?php

namespace Drupal\jcms_rest\Plugin\rest\resource;

use Drupal\Core\Entity\EntityInterface;
use Drupal\jcms_rest\Response\JCMSRestResponse;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "podcast_episode_list_rest_resource",
 *   label = @Translation("Podcast episode list rest resource"),
 *   uri_paths = {
 *     "canonical" = "/podcast-episodes"
 *   }
 * )
 */
class PodcastEpisodeListRestResource extends AbstractRestResourceBase {

  /**
   * Responds to GET requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @todo - elife - nlisgo - Handle version specific requests
   */
  public function get() : JCMSRestResponse {
    $base_query = \Drupal::entityQuery('node')
      ->condition('changed', \Drupal::time()->getRequestTime(), '<')
      ->condition('type', 'podcast_episode');

    if (!$this->viewUnpublished()) {
      $base_query->condition('status', NodeInterface::PUBLISHED);
    }

    $this->filterSubjects($base_query);
    $this->filterContaining($base_query, 'field_episode_chapter.entity.field_related_content', ['article', 'collection']);

    $count_query = clone $base_query;
    $items_query = clone $base_query;
    $response_data = [
      'total' => 0,
      'items' => [],
    ];
    $nodes = [];
    if ($total = $count_query->count()->execute()) {
      $response_data['total'] = (int) $total;
      $this->filterPageAndOrder($items_query, 'field_episode_number.value');
      $nids = $items_query->execute();
      $nodes = Node::loadMultiple($nids);
      if (!empty($nodes)) {
        foreach ($nodes as $node) {
          $response_data['items'][] = $this->getItem($node);
        }
      }
    }
    $response = new JCMSRestResponse($response_data, Response::HTTP_OK, ['Content-Type' => $this->getContentType()]);
    $response->addCacheableDependencies($nodes);
    $this->processResponse($response);
    return $response;
  }

  /**
   * Takes a node and builds an item from it.
   */
  public function getItem(EntityInterface $node) : array {
    /* @var Node $node */
    $this->setSortBy('created', TRUE);
    $item = $this->processDefault($node, (int) $node->get('field_episode_number')->getString(), 'number');

    // Impact statement is optional.
    if ($node->get('field_impact_statement')->count()) {
      $item['impactStatement'] = $this->fieldValueFormatted($node->get('field_impact_statement'));
      if (empty($item['impactStatement'])) {
        unset($item['impactStatement']);
      }
    }

    // Image is required.
    $item['image'] = $this->processFieldImage($node->get('field_image'), TRUE);
    $attribution = $this->fieldValueFormatted($node->get('field_image_attribution'), FALSE, TRUE);
    if (!empty($attribution)) {
      foreach ($item['image'] as $key => $type) {
        $item['image'][$key]['attribution'] = $attribution;
      }
    }

    // mp3 is required.
    $item['sources'] = [
      [
        'mediaType' => 'audio/mpeg',
        'uri' => $node->get('field_episode_mp3')->first()->getValue()['uri'],
      ],
    ];

    return $item;
  }

}
