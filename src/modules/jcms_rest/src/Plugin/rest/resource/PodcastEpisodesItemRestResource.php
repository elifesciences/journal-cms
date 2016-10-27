<?php

namespace Drupal\jcms_rest\Plugin\rest\resource;

use Drupal\Component\Utility\Random;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "podcast_episodes_item_rest_resource",
 *   label = @Translation("Podcast episodes item rest resource"),
 *   uri_paths = {
 *     "canonical" = "/podcast-episodes/{number}"
 *   }
 * )
 */
class PodcastEpisodesItemRestResource extends AbstractRestResourceBase {
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
        $response['impactStatement'] = $node->get('field_impact_statement')->first()->getValue()['value'];
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
            'title' => $chapter_item->get('field_block_title')->first()->getValue()['value'],
            'time' => (int) $chapter_item->get('field_chapter_time')->first()->getValue()['value'],
          ];
          if ($chapter_item->get('field_block_html')->count()) {
            $chapter_values['impactStatement'] = $chapter_item->get('field_block_html')->first()->getValue()['value'];
          }
          if ($chapter_item->get('field_chapter_content')->count()) {
            $chapter_values['content'] = [];
            foreach ($chapter_item->get('field_chapter_content') as $content) {
              $chapter_values['content'][] = $this->prepareDummyContent($content->getValue()['value']);
            }
          }
          $chapters[] = $chapter_values;
        }
        $response['chapters'] = $chapters;
      }

      $response = new JsonResponse($response, Response::HTTP_OK, ['Content-Type' => 'application/vnd.elife.podcast-episode+json;version=1']);
      return $response;
    }

    throw new NotFoundHttpException(t('Podcast episode with ID @id was not found', ['@id' => $number]));
  }

  /**
   * Prepare dummy snippet of article or collection.
   *
   * @todo - elife - nlisgo - swap out for actual article or collection snippets.
   *
   * @param string $content_id
   * @return array
   */
  public function prepareDummyContent($content_id) {
    $random = new Random();

    // Generate a random name.
    $names = function($preferred_only = FALSE) use ($random) {
      $names = [ucfirst($random->word(rand(4, 9))), ucfirst($random->word(rand(4, 9)))];
      if ($preferred_only) {
        return implode(' ', $names);
      }
      else {
        return [
          'preferred' => implode(' ', $names),
          'index' => implode(', ', array_reverse($names)),
        ];
      }
    };

    // Prepare dummy thumbnail references.
    $thumbnail = function () {
      static $image;

      if (!isset($image)) {
        $image = $this->getImageSizes('thumbnail');
        $image['thumbnail']['alt'] = '';
        foreach ($image['thumbnail']['sizes'] as $ar => $sizes) {
          foreach ($sizes as $width => $height) {
            $image['thumbnail']['sizes'][$ar][$width] = 'https://placehold.it/' . $width . 'x' . $height;
          }
        }
      }

      return $image;
    };

    // Generate random content id string.
    $id = function() use ($random) {
      return strtolower(substr(base64_encode($random->string()), 0, 8));
    };

    // Select a single random item from array.
    $random_item = function ($array) {
      shuffle($array);
      return $array[0];
    };

    // Prepare dummy collection snippet.
    if (preg_match('/^collections', $content_id)) {
      $content = [
        'type' => 'collection',
        'id' => $id(),
        'title' => $random->sentences(3),
        'updated' => $this->formatDate(),
        'image' => $thumbnail(),
        'selectedCurator' => [
          'id' => $id(),
          'name' => $names(),
        ],
      ];
    }
    // Prepare dummy article snippet.
    else {
      $content = [
        'type' => $random_item(['correction', 'editorial', 'feature', 'insight', 'research-advance', 'research-article', 'research-exchange', 'retraction', 'registered-report', 'replication-study', 'short-report', 'tools-resources']),
        'status' => $random_item(['poa', 'vor']),
        'id' => $content_id,
        'version' => rand(1, 3),
        'doi' => '10.7554/eLife.' . $content_id,
        'authorLine' => $names(TRUE) . $random_item(['', ' et al']),
        'title' => $random->sentences(3),
        'published' => $this->formatDate(),
        'statusDate' => $this->formatDate(),
        'volume' => rand(1, 5),
        'elocationId' => 'e' . $content_id,
        'pdf' => 'https://elifesciences.org/content/%d/e' . $content_id . '.pdf',
      ];

      // Insert the volume number into pdf url.
      $content['pdf'] = sprintf($content['pdf'], $content['volume']);

      // Optionally display impact statement.
      if (rand(0, 2) > 0) {
        $content['impactStatement'] = $random->sentences(4);
      }

      // Optionally display dummy image.
      if (rand(0, 2) === 0) {
        $content['image'] = $thumbnail();
      }
    }

    return $content;
  }

}
