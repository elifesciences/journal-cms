<?php

namespace Drupal\jcms_rest\Plugin\rest\resource;

use Drupal\taxonomy\Entity\Term;
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
   * @throws \Drupal\jcms_rest\Exception\JCMSNotFoundHttpException
   */
  public function get(string $id = NULL) : JCMSRestResponse {
    if ($this->checkId($id, 'subject')) {
      $query = \Drupal::entityQuery('taxonomy_term')
        ->accessCheck(TRUE)
        ->condition('vid', 'subjects')
        ->condition('field_subject_id.value', $id);

      $tids = $query->execute();
      if ($tids) {
        $tid = reset($tids);
        /** @var \Drupal\taxonomy\Entity\Term $term */
        $term = Term::load($tid);

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

        if ($term->get('field_impact_statement')->count() && $impact = $this->fieldValueFormatted($term->get('field_impact_statement'))) {
          $response['impactStatement'] = $impact;
        }

        if ($term->get('field_aims_and_scope')->count() && $aims = $this->splitParagraphs($this->fieldValueFormatted($term->get('field_aims_and_scope'), FALSE))) {
          $response['aimsAndScope'][] = [
            'type' => 'paragraph',
            'text' => implode(' ', $aims),
          ];
        }

        $response = new JCMSRestResponse($response, Response::HTTP_OK, ['Content-Type' => $this->getContentType()]);
        $response->addCacheableDependency($term);
        $this->processResponse($response);
        return $response;
      }
    }

    throw new JCMSNotFoundHttpException(t('Subject with ID @id was not found', ['@id' => $id]));
  }

}
