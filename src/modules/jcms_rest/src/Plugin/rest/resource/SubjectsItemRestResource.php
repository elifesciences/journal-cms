<?php

namespace Drupal\jcms_rest\Plugin\rest\resource;

use Drupal\image\Entity\ImageStyle;
use Drupal\jcms_rest\Exception\JCMSNotFoundHttpException;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
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
class SubjectsItemRestResource extends AbstractRestResourceBase {
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

    $tids = $query->execute();
    if ($tids) {
      $tid = reset($tids);
      /* @var \Drupal\taxonomy\Entity\Term $term */
      $term = \Drupal\taxonomy\Entity\Term::load($tid);

      $response = [
        'id' => $id,
        'name' => $term->toLink()->getText(),
      ];
      $response['image'] = $this->processFieldImage($term->get('field_image'), TRUE);

      if ($term->get('field_impact_statement')->count()) {
        $response['impactStatement'] = $term->get('field_impact_statement')->first()->getValue()['value'];
      }

      $response = new JsonResponse($response, Response::HTTP_OK, ['Content-Type' => 'application/vnd.elife.subject+json;version=1']);
      return $response;
    }

    throw new JCMSNotFoundHttpException(t('Subject with ID @id was not found', ['@id' => $id]), NULL, 'application/problem+json');
  }

}
