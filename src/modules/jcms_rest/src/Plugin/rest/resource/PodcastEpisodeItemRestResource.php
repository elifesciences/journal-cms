<?php

namespace Drupal\jcms_rest\Plugin\rest\resource;

use DateInterval;
use Drupal\Core\Database\Database;
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
 *   id = "podcast_episode_item_rest_resource",
 *   label = @Translation("Podcast episode item rest resource"),
 *   uri_paths = {
 *     "canonical" = "/podcast-episodes/{number}"
 *   }
 * )
 */
class PodcastEpisodeItemRestResource extends AbstractRestResourceBase {

  /**
   * Responds to GET requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @throws JCMSNotFoundHttpException
   */
  public function get(int $number) : JCMSRestResponse {
    $query = \Drupal::entityQuery('node')
      ->condition('changed', \Drupal::time()->getRequestTime(), '<')
      ->condition('type', 'podcast_episode')
      ->condition('field_episode_number.value', $number);

    if (!$this->viewUnpublished()) {
      $query->condition('status', NodeInterface::PUBLISHED);
    }

    $nids = $query->execute();
    if ($nids) {
      $nid = reset($nids);
      /* @var \Drupal\node\Entity\Node $node */
      $node = Node::load($nid);

      $response = $this->processDefault($node, $number, 'number');

      // Image is required.
      $response['image'] = $this->processFieldImage($node->get('field_image'), TRUE);
      $attribution = $this->fieldValueFormatted($node->get('field_image_attribution'), FALSE, TRUE);
      if (!empty($attribution)) {
        foreach ($response['image'] as $key => $type) {
          $response['image'][$key]['attribution'] = $attribution;
        }
      }

      // mp3 is required.
      $response['sources'] = [
        [
          'mediaType' => 'audio/mpeg',
          'uri' => $node->get('field_episode_mp3')->first()->getValue()['uri'],
        ],
      ];

      // Impact statement is optional.
      if ($node->get('field_impact_statement')->count()) {
        $response['impactStatement'] = $this->fieldValueFormatted($node->get('field_impact_statement'));
        if (empty($response['impactStatement'])) {
          unset($response['impactStatement']);
        }
      }

      if ($node->get('field_episode_chapter')->count()) {
        $response['chapters'] = [];
        foreach ($node->get('field_episode_chapter')->referencedEntities() as $chapter) {
          /* @var Node $chapter */
          if ($chapter->isPublished() || $this->viewUnpublished()) {
            $response['chapters'][] = $this->getChapterItem($chapter, 0);
          }
        }
      }

      $response = new JCMSRestResponse($response, Response::HTTP_OK, ['Content-Type' => $this->getContentType()]);
      $response->addCacheableDependency($node);
      $this->processResponse($response);
      return $response;
    }

    throw new JCMSNotFoundHttpException(t('Podcast episode with ID @id was not found', ['@id' => $number]));
  }

  /**
   * Takes a chapter node and builds an item from it.
   */
  public function getChapterItem(EntityInterface $node, int $number = NULL, $add_episode = FALSE) : array {
    /* @var Node $node */
    static $count = 0;
    $count++;

    $query = Database::getConnection()->select('node__field_episode_chapter', 'ec');
    $query->addField('ec', 'delta');
    $query->innerJoin('node__field_episode_chapter', 'ec2', 'ec2.entity_id = ec.entity_id AND ec2.delta <= ec.delta');
    $query->condition('ec.field_episode_chapter_target_id', $node->id());

    if ($number === 0) {
      if ($result = $query->countQuery()->execute()->fetchField()) {
        $number = (int) $result;
      }
    }

    if ($node->get('field_chapter_time')->getValue()) {
      $time = new DateInterval($node->get('field_chapter_time')->getString());
    }
    else {
      $time = 0;
    }

    $chapter_values = [
      'number' => $number ?: $count,
      'title' => $node->getTitle(),
      'time' => ($time instanceof DateInterval) ? ($time->i * 60 + $time->s) : $time,
    ];
    if ($node->get('field_long_title')->count()) {
      $chapter_values['longTitle'] = $this->fieldValueFormatted($node->get('field_long_title'));
      if (!empty($chapter_values['longTitle'])) {
        unset($chapter_values['longTitle']);
      }
    }
    if ($node->get('field_impact_statement')->count()) {
      $chapter_values['impactStatement'] = $this->fieldValueFormatted($node->get('field_impact_statement'));
      if (empty($chapter_values['impactStatement'])) {
        unset($chapter_values['impactStatement']);
      }
    }
    if ($node->get('field_related_content')->count()) {
      $chapter_content = [];
      $collection_rest_resource = new CollectionListRestResource([], 'collection_list_rest_resource', [], $this->serializerFormats, $this->logger);
      foreach ($node->get('field_related_content') as $content) {
        /* @var \Drupal\node\Entity\Node $content_node */
        $content_node = $content->get('entity')->getTarget()->getValue();
        switch ($content_node->getType()) {
          case 'collection':
            $chapter_content[] = ['type' => 'collection'] + $collection_rest_resource->getItem($content_node);
            break;

          case 'article':
            if ($article = $this->getArticleSnippet($content_node)) {
              $chapter_content[] = $article;
            }
            break;

          default:
        }
      }

      if (!empty($chapter_content)) {
        $chapter_values['content'] = $chapter_content;
      }
    }

    if ($add_episode) {
      $chapter_values = ['chapter' => $chapter_values];
      $query->addField('ec', 'entity_id');
      $query->range(0, 1);
      if ($result = $query->execute()->fetchObject()) {
        /* @var \Drupal\node\Entity\Node $episode */
        $episode = Node::load($result->entity_id);
        $podcast_episode_list = new PodcastEpisodeListRestResource([], 'podcast_episode_list_rest_resource', [], $this->serializerFormats, $this->logger);
        $chapter_values['episode'] = $podcast_episode_list->getItem($episode);
      }
    }

    return $chapter_values;
  }

}
