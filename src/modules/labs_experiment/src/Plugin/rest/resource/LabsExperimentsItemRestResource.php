<?php

namespace Drupal\labs_experiment\Plugin\rest\resource;

use Drupal\image\Entity\ImageStyle;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "labs_experiments_item_rest_resource",
 *   label = @Translation("Labs experiments item rest resource"),
 *   uri_paths = {
 *     "canonical" = "/labs-experiments/{number}"
 *   }
 * )
 */
class LabsExperimentsItemRestResource extends ResourceBase {
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
      ->condition('type', 'labs_experiment')
      ->condition('field_experiment_number.value', $number);

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

      $handle_paragraphs = function($content) use (&$handle_paragraphs) {
        $result = [];
        foreach ($content as $paragraph) {
          $content_item = $paragraph->get('entity')->getTarget()->getValue();
          $content_type = $content_item->getType();
          $result_item = [
            'type' => $content_type,
          ];
          switch ($content_type) {
            case 'section':
              $result_item['title'] = $content_item->get('field_block_title')->first()->getValue()['value'];
              $result_item['content'] = $handle_paragraphs($content_item->get('field_block_content'));
              break;
            case 'paragraph':
              $result_item['text'] = $content_item->get('field_block_text')->first()->getValue()['value'];
              break;
            case 'image':
              $image = $content_item->get('field_block_image')->first();
              $result_item['alt'] = $image->getValue()['alt'];
              $result_item['uri'] = file_create_url($image->get('entity')->getTarget()->get('uri')->first()->getValue()['value']);
              if ($content_item->get('field_block_text')->count()) {
                $result_item['caption'] = $content_item->get('field_block_text')->first()->getValue()['value'];
              }
              break;
            case 'blockquote':
              $result_item['text'] = $content_item->get('field_block_text')->first()->getValue()['value'];
              if ($content_item->get('field_block_citation')->count()) {
                $result_item['citation'] = $content_item->get('field_block_citation')->first()->getValue()['value'];
              }
              break;
          }

          $result[] = $result_item;
        }

        return $result;
      };

      $content = $handle_paragraphs($node->get('field_content'));
      if (!empty($content)) {
        $response['content'] = $content;
      }

      return new ResourceResponse($response);
    }

    throw new NotFoundHttpException(t('Lab experiment with ID @id was not found', ['@id' => $number]));
  }

}
