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
 *   id = "subjects_item_rest_resource",
 *   label = @Translation("Subjects item rest resource"),
 *   uri_paths = {
 *     "canonical" = "subjects/{id}"
 *   }
 * )
 */
class SubjectsItemRestResource extends ResourceBase {
  /**
   * Responds to GET requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function get($id = NULL) {
    $query = \Drupal::entityQuery('taxonomy_term')
      ->condition('vid', 'subjects')
      ->condition('field_subject_id.value', $id);
    $status = Response::HTTP_OK;

    $tids = $query->execute();
    if ($tids) {
      $tid = reset($tids);
      /* @var \Drupal\taxonomy\Entity\Term $term */
      $term = \Drupal\taxonomy\Entity\Term::load($tid);

      $response = [
        'id' => $id,
        'name' => $term->toLink()->getText(),
        'image' => [
          'alt' => $term->get('field_image')->first()->getValue()['alt'],
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

      $image_uri = $term->get('field_image')->first()->get('entity')->getTarget()->get('uri')->first()->getValue()['value'];
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

      if ($term->get('field_impact_statement')->count()) {
        $response['impactStatement'] = $term->get('field_impact_statement')->first()->getValue()['value'];
      }

      $resource_response = new ResourceResponse($response, $status);
      // @todo - elife - nlisgo - Implement caching with options as a cacheable dependency, disable for now.
      $resource_response->addCacheableDependency(NULL);

      return $resource_response;
    }

    throw new NotFoundHttpException(t('Subject with ID @id was not found', ['@id' => $id]));
  }

}
