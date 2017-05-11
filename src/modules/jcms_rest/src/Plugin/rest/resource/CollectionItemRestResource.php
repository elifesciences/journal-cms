<?php

namespace Drupal\jcms_rest\Plugin\rest\resource;

use Drupal\jcms_rest\Exception\JCMSNotFoundHttpException;
use Drupal\jcms_rest\Response\JCMSRestResponse;
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
  /**
   * Responds to GET requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @param string $id
   * @return array|\Symfony\Component\HttpFoundation\JsonResponse
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function get($id) {
    $query = \Drupal::entityQuery('node')
      ->condition('status', NODE_PUBLISHED)
      ->condition('changed', REQUEST_TIME, '<')
      ->condition('type', 'collection')
      ->condition('uuid', '%' . $id, 'LIKE');

    $nids = $query->execute();
    if ($nids) {
      $nid = reset($nids);
      /* @var \Drupal\node\Entity\Node $node */
      $node = \Drupal\node\Entity\Node::load($nid);

      $this->setSortBy('changed');
      $response = $this->processDefault($node, $id);

      // Subtitle is optional.
      if ($node->get('field_subtitle')->count()) {
        $response['subTitle'] = $node->get('field_subtitle')->getString();
      }

      // Image is optional.
      if ($image = $this->processFieldImage($node->get('field_image'), FALSE)) {
        $response['image'] = $image;
      }

      // Impact statement is optional.
      if ($node->get('field_impact_statement')->count()) {
        $response['impactStatement'] = $this->fieldValueFormatted($node->get('field_impact_statement'));
      }

      // Subjects are optional.
      $subjects = $this->processSubjects($node->get('field_subjects'));
      if (!empty($subjects)) {
        $response['subjects'] = $subjects;
      }

      // Curators are required.
      $co = 0;
      $people_rest_resource = new PersonListRestResource([], 'person_list_rest_resource', [], $this->serializerFormats, $this->logger);
      $response['curators'] = [];
      foreach ($node->get('field_curators') as $curator) {
        $curator_item = $people_rest_resource->getItem($curator->get('entity')->getTarget()->getValue());
        $response['curators'][] = $curator_item;
        if ($co === 0) {
          $response['selectedCurator'] = $curator_item;
        }
        elseif ($co === 1) {
          $response['selectedCurator']['etAl'] = TRUE;
        }
        $co++;
      }

      // Summary is optional.
      if ($content = $this->processFieldContent($node->get('field_summary'))) {
        $response['summary'] = $content;
      }

      // Collection content is required.
      $response['content'] = [];

      $blog_article_rest_resource = new BlogArticleListRestResource([], 'blog_article_list_rest_resource', [], $this->serializerFormats, $this->logger);
      $interview_rest_resource = new InterviewListRestResource([], 'interview_list_rest_resource', [], $this->serializerFormats, $this->logger);

      foreach (['content' => 'field_collection_content', 'relatedContent' => 'field_collection_related_content'] as $k => $field) {
        foreach ($node->get($field) as $content) {
          /* @var \Drupal\node\Entity\Node $content_node */
          $content_node = $content->get('entity')->getTarget()->getValue();
          switch ($content_node->getType()) {
            case 'blog_article':
              $response[$k][] = ['type' => 'blog-article'] + $blog_article_rest_resource->getItem($content_node);
              break;
            case 'interview':
              $response[$k][] = ['type' => 'interview'] + $interview_rest_resource->getItem($content_node);
              break;
            case 'article':
              if ($snippet = $this->getArticleSnippet($content_node)) {
                $response[$k][] = $snippet;
              }
              break;
            default:
          }
        }
      }

      // Podcasts are optional.
      if ($node->get('field_collection_podcasts')->count()) {
        $response['podcastEpisodes'] = [];
        $podcast_rest_resource = new PodcastEpisodeListRestResource([], 'podcast_episode_list_rest_resource', [], $this->serializerFormats, $this->logger);
        foreach ($node->get('field_collection_podcasts') as $podcast) {
          $response['podcastEpisodes'][] = $podcast_rest_resource->getItem($podcast->get('entity')->getTarget()->getValue());
        }
      }

      $response = new JCMSRestResponse($response, Response::HTTP_OK, ['Content-Type' => $this->getContentType()]);
      $response->addCacheableDependency($node);
      return $response;
    }

    throw new JCMSNotFoundHttpException(t('Collection with ID @id was not found', ['@id' => $id]));
  }

}
