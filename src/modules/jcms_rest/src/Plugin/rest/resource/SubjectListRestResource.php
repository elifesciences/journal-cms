<?php

namespace Drupal\jcms_rest\Plugin\rest\resource;

use Drupal\Core\Entity\EntityInterface;
use Drupal\jcms_rest\Response\JCMSRestResponse;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "subject_list_rest_resource",
 *   label = @Translation("Subject list rest resource"),
 *   uri_paths = {
 *     "canonical" = "/subjects"
 *   }
 * )
 */
class SubjectListRestResource extends AbstractRestResourceBase {

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
    $terms = [];
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
    $response = new JCMSRestResponse($response_data, Response::HTTP_OK, ['Content-Type' => $this->getContentType()]);
    $response->addCacheableDependencies($terms);
    $this->processResponse($response);
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
      'id' => $term->get('field_subject_id')->getString(),
      'name' => $term->toLink()->getText(),
    ];
    $item['image'] = $this->processFieldImage($term->get('field_image'), TRUE);
    $attribution = $this->fieldValueFormatted($term->get('field_image_attribution'), FALSE, TRUE);
    if (!empty($attribution)) {
      foreach ($item['image'] as $key => $type) {
        $item['image'][$key]['attribution'] = $attribution;
      }
    }

    if ($term->get('field_impact_statement')->count()) {
      $item['impactStatement'] = $this->fieldValueFormatted($term->get('field_impact_statement'));
      if (empty($item['impactStatement'])) {
        unset($item['impactStatement']);
      }
    }
    return $item;
  }

}
