<?php

namespace Drupal\jcms_rest\Plugin\rest\resource;

use Drupal\image\Entity\ImageStyle;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
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
class PodcastEpisodesItemRestResource extends ResourceBase {
  /**
   * Responds to GET requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function get($number = NULL) {
    $query = \Drupal::entityQuery('node')
      ->condition('status', NODE_PUBLISHED)
      ->condition('changed', REQUEST_TIME, '<')
      ->condition('type', 'podcast_episode')
      ->condition('field_episode_number.value', $number);
    $status = Response::HTTP_OK;

    $nids = $query->execute();
    if ($nids) {
      $nid = reset($nids);
      /* @var \Drupal\node\Entity\Node $node */
      $node = \Drupal\node\Entity\Node::load($nid);

      $response = [
        'number' => $number,
        'title' => $node->getTitle(),
        'published' => \Drupal::service('date.formatter')->format($node->getCreatedTime(), 'html_datetime'),
        'image' => [
          'alt' => $node->get('field_image')->first()->getValue()['alt'],
          'sizes' => [
            '2:1' => [
              900 => '450',
              1800 => '900',
            ],
            '16:9' => [
              250 => '141',
              500 => '282',
            ],
            '1:1' => [
              70 => '70',
              140 => '140',
            ],
          ],
        ],
      ];

      $image_uri = $node->get('field_image')->first()->get('entity')->getTarget()->get('uri')->first()->getValue()['value'];
      foreach ($response['image']['sizes'] as $ar => $sizes) {
        foreach ($sizes as $width => $height) {
          $image_style = [
            'crop',
            str_replace(':', 'x', $ar),
            $width . 'x' . $height,
          ];
          $response['image']['sizes'][$ar][$width] = ImageStyle::load(implode('_', $image_style))->buildUrl($image_uri);
        }
      }

      if ($node->get('field_impact_statement')->count()) {
        $response['impactStatement'] = $node->get('field_impact_statement')->first()->getValue()['value'];
      }

      if ($node->get('field_subjects')->count()) {
        $item['subjects'] = [];
        /** @var \Drupal\Core\Entity\Entity $field_subjects */
        $field_subjects = $node->get('field_subjects');
        /* @var \Drupal\taxonomy\Entity\Term $term */
        foreach ($field_subjects->referencedEntities() as $term) {
          $item['subjects'][] = $term->get('field_subject_id')->first()->getValue()['value'];
        }
      }

      return new ResourceResponse($response, $status);
    }

    throw new NotFoundHttpException(t('Lab experiment with ID @id was not found', ['@id' => $number]));
  }

}
