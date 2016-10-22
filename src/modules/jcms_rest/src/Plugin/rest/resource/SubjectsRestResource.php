<?php

namespace Drupal\jcms_rest\Plugin\rest\resource;

use Drupal\Core\Entity\EntityInterface;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "subjects_list_rest_resource",
 *   label = @Translation("Subjects list rest resource"),
 *   uri_paths = {
 *     "canonical" = "/subjects"
 *   }
 * )
 */
class SubjectsRestResource extends AbstractRestResourceBase {

  /**
   * Responds to GET requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   *
   * @todo - elife - nlisgo - Handle version specific requests
   */
  public function get() {
    $base_query = \Drupal::entityQuery('taxonomy_term')
      ->condition('vid', 'subjects');
    $count_query = clone $base_query;
    $items_query = clone $base_query;
    $response_data = [
      'total' => 0,
      'items' => [],
    ];
    if ($total = $count_query->count()->execute()) {
      $response_data['total'] = (int) $total;
      $this->filterPageAndOrder($items_query, 'name');
      $tids = $items_query->execute();
      $terms = Term::loadMultiple($tids);
      if (!empty($terms)) {
        foreach ($terms as $term) {
          $response_data['items'][] = $this->getItem($term);
        }
      }
    }
    $response = new JsonResponse($response_data, Response::HTTP_OK, ['Content-Type' => 'application/vnd.elife.subject-list+json;version=1']);
    return $response;
  }

  /**
   * Takes a taxonomy term and builds an item from it.
   *
   * @param \Drupal\Core\Entity\EntityInterface $term
   *
   * @return array
   */
  public function getItem(EntityInterface $term) {
    /* @var Term $term */
    $item = [
      'id' => $term->get('field_subject_id')->first()->getValue()['value'],
      'name' => $term->toLink()->getText(),
    ];
    $item['image'] = $this->processFieldImage($term->get('field_image'), TRUE);

    if ($term->get('field_impact_statement')->count()) {
      $item['impactStatement'] = $term->get('field_impact_statement')->first()->getValue()['value'];
    }
    return $item;
  }

}
