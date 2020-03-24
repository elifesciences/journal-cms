<?php

namespace Drupal\jcms_rest\Plugin\rest\resource;

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
 *   id = "regional_collection_item_rest_resource",
 *   label = @Translation("Regional collection item rest resource"),
 *   uri_paths = {
 *     "canonical" = "/regional-collections/{id}"
 *   }
 * )
 */
class RegionalCollectionItemRestResource extends AbstractRestResourceBase {
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
        ->condition('type', 'regional_collection')
        ->condition('uuid', '%' . $id, 'LIKE');

      if (!$this->viewUnpublished()) {
        $query->condition('status', NodeInterface::PUBLISHED);
      }

      $nids = $query->execute();
      if ($nids) {
        $nid = reset($nids);
        /* @var \Drupal\node\Entity\Node $node */
        $node = Node::load($nid);

        $this->setSortBy('changed');
        $response = $this->processDefault($node, $id);

        // Image is optional.
        if ($image = $this->processFieldImage($node->get('field_image'), FALSE)) {
          $attribution = $this->fieldValueFormatted($node->get('field_image_attribution'), FALSE, TRUE);
          if (!empty($attribution)) {
            foreach ($image as $key => $type) {
              $image[$key]['attribution'] = $attribution;
            }
          }
          $response['image'] = $image;
        }

        // Impact statement is optional.
        if ($node->get('field_impact_statement')->count()) {
          $response['impactStatement'] = $this->fieldValueFormatted($node->get('field_impact_statement'));
          if (empty($response['impactStatement'])) {
            unset($response['impactStatement']);
          }
        }

        // Subjects are optional.
        $subjects = $this->processSubjects($node->get('field_subjects'));
        if (!empty($subjects)) {
          $response['subjects'] = $subjects;
        }

        // Curators are required.
        $co = 0;
        $people_rest_resource = new PersonListRestResource([], 'person_list_rest_resource', [], $this->serializerFormats, $this->logger);
        $response['editors'] = [];
        foreach ($node->get('field_editors')->referencedEntities() as $editor) {
          /* @var Node $editor */
          if ($editor->isPublished() || $this->viewUnpublished()) {
            $editor_item = $people_rest_resource->getItem($editor);
            $response['editors'][] = $editor_item;
          }
        }

        // Summary is optional.
        if ($content = $this->processFieldContent($node->get('field_summary'))) {
          $response['summary'] = $content;
        }

        // Collection content is required.
        $response['content'] = [];

        $blog_article_rest_resource = new BlogArticleListRestResource([], 'blog_article_list_rest_resource', [], $this->serializerFormats, $this->logger);
        $event_rest_resource = new EventListRestResource([], 'event_list_rest_resource', [], $this->serializerFormats, $this->logger);
        $interview_rest_resource = new InterviewListRestResource([], 'interview_list_rest_resource', [], $this->serializerFormats, $this->logger);

        foreach (['content' => 'field_collection_content', 'relatedContent' => 'field_collection_related_content'] as $k => $field) {
          foreach ($node->get($field)->referencedEntities() as $content) {
            /* @var Node $content */
            if ($content->isPublished() || $this->viewUnpublished()) {
              switch ($content->getType()) {
                case 'blog_article':
                  $response[$k][] = ['type' => 'blog-article'] + $blog_article_rest_resource->getItem($content);
                  break;

                case 'event':
                  if ($this->acceptVersion < 2) {
                    throw new JCMSNotAcceptableHttpException('This collection requires version 2+.');
                  }
                  $response[$k][] = ['type' => 'event'] + $event_rest_resource->getItem($content);
                  break;

                case 'interview':
                  $response[$k][] = ['type' => 'interview'] + $interview_rest_resource->getItem($content);
                  break;

                case 'article':
                  if ($snippet = $this->getArticleSnippet($content)) {
                    $response[$k][] = $snippet;
                  }
                  break;

                case 'digest':
                  if ($snippet = $this->getDigestSnippet($content)) {
                    if ($this->acceptVersion < 2) {
                      throw new JCMSNotAcceptableHttpException('This collection requires version 2+.');
                    }
                    $response[$k][] = $snippet;
                  }

                default:
              }
            }
          }
        }

        // Podcasts are optional.
        if ($node->get('field_collection_podcasts')->count()) {
          $response['podcastEpisodes'] = [];
          $podcast_rest_resource = new PodcastEpisodeListRestResource([], 'podcast_episode_list_rest_resource', [], $this->serializerFormats, $this->logger);
          foreach ($node->get('field_collection_podcasts')->referencedEntities() as $podcast) {
            /* @var Node $podcast */
            if ($podcast->isPublished() || $this->viewUnpublished()) {
              $response['podcastEpisodes'][] = $podcast_rest_resource->getItem($podcast);
            }
          }
        }

        $response = new JCMSRestResponse($response, Response::HTTP_OK, ['Content-Type' => $this->getContentType()]);
        $response->addCacheableDependency($node);
        $this->processResponse($response);
        return $response;
      }
    }

    throw new JCMSNotFoundHttpException(t('Regional collection with ID @id was not found', ['@id' => $id]));
  }

}
