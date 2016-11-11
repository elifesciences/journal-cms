<?php

namespace Drupal\jcms_rest\Plugin\rest\resource;

use Drupal\Component\Utility\Random;
use Drupal\jcms_rest\Exception\JCMSNotFoundHttpException;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\JsonResponse;
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
   * @param int $number
   * @return array|\Symfony\Component\HttpFoundation\JsonResponse
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function get(int $number) {
    $query = \Drupal::entityQuery('node')
      ->condition('status', NODE_PUBLISHED)
      ->condition('changed', REQUEST_TIME, '<')
      ->condition('type', 'podcast_episode')
      ->condition('field_episode_number.value', $number);

    $nids = $query->execute();
    if ($nids) {
      $nid = reset($nids);
      /* @var \Drupal\node\Entity\Node $node */
      $node = \Drupal\node\Entity\Node::load($nid);

      $response = $this->processDefault($node, $number, 'number');

      // Image is required.
      $response['image'] = $this->processFieldImage($node->get('field_image'), TRUE);

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
      }

      // Subjects are optional.
      $subjects = $this->processSubjects($node->get('field_subjects'));
      if (!empty($subjects)) {
        $response['subjects'] = $subjects;
      }

      if ($node->get('field_episode_chapter')->count()) {
        $chapters = [];
        $count = 0;
        foreach ($node->get('field_episode_chapter') as $chapter) {
          $chapter_item = $chapter->get('entity')->getTarget()->getValue();
          $count++;
          $chapter_values = [
            'number' => $count,
            'title' => $chapter_item->get('field_block_title')->getString(),
            'time' => (int) $chapter_item->get('field_chapter_time')->getString(),
          ];
          if ($chapter_item->get('field_block_html')->count()) {
            $chapter_values['impactStatement'] = $this->fieldValueFormatted($chapter_item->get('field_block_html'));
          }
          if ($chapter_item->get('field_chapter_content')->count()) {
            $chapter_values['content'] = [];
            foreach ($chapter_item->get('field_chapter_content') as $content) {
              $chapter_values['content'][] = $this->prepareContent($content->getString());
            }
          }
          $chapters[] = $chapter_values;
        }
        $response['chapters'] = $chapters;
      }

      $response = new JsonResponse($response, Response::HTTP_OK, ['Content-Type' => 'application/vnd.elife.podcast-episode+json;version=1']);
      return $response;
    }

    throw new JCMSNotFoundHttpException(t('Podcast episode with ID @id was not found', ['@id' => $number]), NULL, 'application/problem+json');
  }

  /**
   * Prepare snippet of article or collection.
   *
   * @todo - elife - nlisgo - swap out for actual article snippets.
   *
   * @param string $content_id
   * @return array
   */
  public function prepareContent($content_id) {
    // Display collection snippet.
    if (preg_match('~^collections/(?P<id>[0-9]+)~', $content_id, $match)) {
      $collection_rest_resource = new CollectionListRestResource([], 'collection_list_rest_resource', [], $this->serializerFormats, $this->logger);
      $content = ['type' => 'collection'] + $collection_rest_resource->getItem(Node::load($match['id']));
    }
    // Prepare dummy article snippet.
    else {
      $content = $this->dummyArticle($content_id);
    }

    return $content;
  }

}
