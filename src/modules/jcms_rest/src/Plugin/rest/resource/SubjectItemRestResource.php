<?php

namespace Drupal\jcms_rest\Plugin\rest\resource;

use Drupal\jcms_rest\Exception\JCMSNotFoundHttpException;
use Drupal\jcms_rest\Response\JCMSRestResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "subject_item_rest_resource",
 *   label = @Translation("Subject item rest resource"),
 *   uri_paths = {
 *     "canonical" = "/subjects/{id}"
 *   }
 * )
 */
class SubjectItemRestResource extends AbstractRestResourceBase {
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
      $attribution = $this->fieldValueFormatted($term->get('field_image_attribution'), FALSE, TRUE);
      if (!empty($attribution)) {
        foreach ($response['image'] as $key => $type) {
          $response['image'][$key]['attribution'] = $attribution;
        }
      }

      if ($term->get('field_impact_statement')->count()) {
        $response['impactStatement'] = $this->fieldValueFormatted($term->get('field_impact_statement'));
        if (empty($response['impactStatement'])) {
          unset($response['impactStatement']);
        }
      }

      $response = new JCMSRestResponse($response, Response::HTTP_OK, ['Content-Type' => $this->getContentType()]);
      $response->addCacheableDependency($term);
      return $response;
    }

    throw new JCMSNotFoundHttpException(t('Subject with ID @id was not found', ['@id' => $id]));
  }

}
