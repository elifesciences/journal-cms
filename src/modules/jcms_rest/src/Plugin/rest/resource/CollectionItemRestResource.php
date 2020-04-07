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
        ->condition('changed', \Drupal::time()->getRequestTime(), '<')
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

    // Summary is optional.
    if ($summary = $this->processFieldContent($node->get('field_summary'))) {
      $item['summary'] = $summary;
    }

    // Collection content is required.
    $item['content'] = [];

    $blog_article_rest_resource = new BlogArticleListRestResource([], 'blog_article_list_rest_resource', [], $this->serializerFormats, $this->logger);
    $event_rest_resource = new EventListRestResource([], 'event_list_rest_resource', [], $this->serializerFormats, $this->logger);
    $interview_rest_resource = new InterviewListRestResource([], 'interview_list_rest_resource', [], $this->serializerFormats, $this->logger);

    foreach (['content' => 'field_collection_content', 'relatedContent' => 'field_collection_related_content'] as $k => $field) {
      foreach ($node->get($field)->referencedEntities() as $content) {
        /* @var Node $content */
        if ($content->isPublished() || $this->viewUnpublished()) {
          switch ($content->getType()) {
            case 'blog_article':
              $item[$k][] = ['type' => 'blog-article'] + $blog_article_rest_resource->getItem($content);
              break;

            case 'event':
              if ($this->acceptVersion < 2) {
                throw new JCMSNotAcceptableHttpException('This collection requires version 2+.');
              }
              $item[$k][] = ['type' => 'event'] + $event_rest_resource->getItem($content);
              break;

            case 'interview':
              $item[$k][] = ['type' => 'interview'] + $interview_rest_resource->getItem($content);
              break;

            case 'article':
              if ($snippet = $this->getArticleSnippet($content)) {
                $item[$k][] = $snippet;
              }
              break;

            case 'digest':
              if ($snippet = $this->getDigestSnippet($content)) {
                if ($this->acceptVersion < 2) {
                  throw new JCMSNotAcceptableHttpException('This collection requires version 2+.');
                }
                $item[$k][] = $snippet;
              }

            default:
          }
        }
      }
    }

    // Podcasts are optional.
    if ($node->get('field_collection_podcasts')->count()) {
      $item['podcastEpisodes'] = [];
      $podcast_rest_resource = new PodcastEpisodeListRestResource([], 'podcast_episode_list_rest_resource', [], $this->serializerFormats, $this->logger);
      foreach ($node->get('field_collection_podcasts')->referencedEntities() as $podcast) {
        /* @var Node $podcast */
        if ($podcast->isPublished() || $this->viewUnpublished()) {
          $item['podcastEpisodes'][] = $podcast_rest_resource->getItem($podcast);
        }
      }
    }

    return $item;
  }

}
