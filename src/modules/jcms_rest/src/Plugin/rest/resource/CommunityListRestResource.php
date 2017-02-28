<?php

namespace Drupal\jcms_rest\Plugin\rest\resource;

use Drupal\Core\Entity\EntityInterface;
use Drupal\jcms_rest\Response\JCMSRestResponse;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "community_list_rest_resource",
 *   label = @Translation("Community list rest resource"),
 *   uri_paths = {
 *     "canonical" = "/community"
 *   }
 * )
 */
class CommunityListRestResource extends AbstractRestResourceBase {

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
    $base_query = \Drupal::entityQuery('node')
      ->condition('status', NODE_PUBLISHED)
      ->condition('changed', REQUEST_TIME, '<')
      ->condition('field_community_list.value', 1);

    $count_query = clone $base_query;
    $items_query = clone $base_query;
    $response_data = [
      'total' => 0,
      'items' => [],
    ];
    $nodes = [];
    if ($total = $count_query->count()->execute()) {
      $response_data['total'] = (int) $total;
      $this->filterPageAndOrder($items_query);
      $nids = $items_query->execute();
      $nodes = Node::loadMultiple($nids);
      if (!empty($nodes)) {
        /* @var Node $node */
        foreach ($nodes as $node) {
          $response_data['items'][] = $this->getItem($node);
        }
      }
    }
    $response = new JCMSRestResponse($response_data, Response::HTTP_OK, ['Content-Type' => $this->getContentType()]);
    $response->addCacheableDependencies($nodes);
    return $response;
  }

  /**
   * Takes a node and builds an item from it.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node
   *
   * @return array
   */
  public function getItem(EntityInterface $node) {
    /* @var Node $node */
    $rest_resource = [
      'blog_article' => new BlogArticleListRestResource([], 'blog_article_list_rest_resource', [], $this->serializerFormats, $this->logger),
      'collection' => new CollectionListRestResource([], 'collection_list_rest_resource', [], $this->serializerFormats, $this->logger),
      'event' => new EventListRestResource([], 'event_list_rest_resource', [], $this->serializerFormats, $this->logger),
      'interview' => new InterviewListRestResource([], 'interview_list_rest_resource', [], $this->serializerFormats, $this->logger),
      'labs_experiment' => new LabsExperimentListRestResource([], 'labs_experiment_list_rest_resource', [], $this->serializerFormats, $this->logger),
      'podcast_episode' => new PodcastEpisodeListRestResource([], 'podcast_episode_list_rest_resource', [], $this->serializerFormats, $this->logger),
    ];

    if ($node->getType() == 'article') {
      return $this->getArticleSnippet($node);
    }
    else {
      return ['type' => str_replace('_', '-', $node->getType())] + $rest_resource[$node->getType()]->getItem($node);
    }
  }

}
