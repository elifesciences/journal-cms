<?php

namespace Drupal\jcms_rest\Plugin\rest\resource;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "cover_list_rest_resource",
 *   label = @Translation("Cover list rest resource"),
 *   uri_paths = {
 *     "canonical" = "/covers"
 *   }
 * )
 */
class CoverListRestResource extends AbstractRestResourceBase {

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
      ->condition('type', 'cover')
      // @todo - elife - nlisgo - check why some legacy images aren't migrating.
      ->exists('field_image');

    $this->filterSubjects($base_query);
    // @todo - elife - nlisgo - allow filter by date range

    $count_query = clone $base_query;
    $items_query = clone $base_query;
    $response_data = [
      'total' => 0,
      'items' => [],
    ];
    if ($total = $count_query->count()->execute()) {
      $response_data['total'] = (int) $total;
      $this->filterPageAndOrder($items_query);
      $nids = $items_query->execute();
      $nodes = Node::loadMultiple($nids);
      if (!empty($nodes)) {
        foreach ($nodes as $node) {
          $response_data['items'][] = $this->getItem($node);
        }
      }
    }
    $response = new JsonResponse($response_data, Response::HTTP_OK, ['Content-Type' => 'application/vnd.elife.cover-list+json;version=1']);
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
    /* @var Node $related */
    $related = $node->get('field_cover_content')->first()->get('entity')->getTarget()->getValue();
    $rest_resource = [
      'blog_article' => new BlogArticleListRestResource([], 'blog_article_list_rest_resource', [], $this->serializerFormats, $this->logger),
      'collection' => new CollectionListRestResource([], 'collection_list_rest_resource', [], $this->serializerFormats, $this->logger),
      'event' => new EventListRestResource([], 'event_list_rest_resource', [], $this->serializerFormats, $this->logger),
      'interview' => new InterviewListRestResource([], 'interview_list_rest_resource', [], $this->serializerFormats, $this->logger),
      'labs_experiment' => new LabsExperimentListRestResource([], 'labs_experiment_list_rest_resource', [], $this->serializerFormats, $this->logger),
      'podcast_episode' => new PodcastEpisodeListRestResource([], 'podcast_episode_list_rest_resource', [], $this->serializerFormats, $this->logger),
    ];

    $cover_values = [
      'title' => $node->getTitle(),
      'image' => $this->processFieldImage($node->get('field_image'), TRUE),
    ];

    if ($related->getType() == 'article') {
      return $cover_values + $this->getArticleSnippet($related);
    }
    else {
      return ['type' => str_replace('_', '-', $related->getType())] + $cover_values + $rest_resource[$related->getType()]->getItem($related);
    }
  }

  /**
   * Apply filter for subjects by amending query.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   */
  protected function filterSubjects(QueryInterface &$query) {
    $subjects = $this->getRequestOption('subject');
    if (!empty($subjects)) {
      $query->condition('field_cover_content.entity.field_subjects.entity.field_subject_id.value', $subjects, 'IN');
    }
  }

}
