<?php

namespace Drupal\jcms_rest\Plugin\rest\resource;

use Drupal\image\Entity\ImageStyle;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "person_rest_resource",
 *   label = @Translation("Person rest resource"),
 *   uri_paths = {
 *     "canonical" = "/people/{id}"
 *   }
 * )
 */
class PeopleItemRestResource extends AbstractRestResourceBase {
  /**
   * Responds to GET requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function get($id) {
    $query = \Drupal::entityQuery('node')
      ->condition('status', NODE_PUBLISHED)
      ->condition('changed', REQUEST_TIME, '<')
      ->condition('type', 'person')
      ->condition('uuid', '%' . $id, 'LIKE');

    // @todo - elife - nlisgo - Handle version specific requests
    // @todo - elife - nlisgo - Handle content negotiation

    $nids = $query->execute();
    if ($nids) {
      $nid = reset($nids);
      /* @var \Drupal\node\Entity\Node $node */
      $node = \Drupal\node\Entity\Node::load($nid);
      $response = [
        'id' => $id,
        'type' => $node->get('field_person_type')->first()->getValue()['value'],
        'name' => [
          'preferred' => $node->getTitle(),
          'index' => $node->get('field_person_index_name')->first()->getValue()['value'],
        ],
      ];

      // Orcid is optional.
      if ($node->get('field_person_orcid')->count()) {
        $item['orcid'] = $node->get('field_person_orcid')->first()->getValue()['value'];
      }

      // Image is optional.
      if ($image = $this->processFieldImage($node->get('field_image'), FALSE, 'thumbnail')) {
        $response['image'] = $image;
      }

      // Profile description is optional.
      if ($profile = $this->processFieldContent($node->get('field_person_profile'))) {
        $response['profile'] = $profile;
      }

      if ($node->get('field_research_details')->count()) {
        $research = [];
        $research_details_field = $node->get('field_research_details')->first()->get('entity')->getTarget()->getValue();
        if ($research_details_field->get('field_research_expertises')->count()) {
          $research['expertises'] = [];
          $research['focuses'] = [];
          foreach ($research_details_field->get('field_research_expertises') as $expertise) {
            $research['expertises'][] = [
              'id' => $expertise->get('entity')->getValue()->get('field_subject_id')->first()->getValue()['value'],
              'name' => $expertise->get('entity')->getValue()->toLink()->getText(),
            ];
          }
        }
        if ($research_details_field->get('field_research_focuses')->count()) {
          $research['focuses'] = [];
          foreach ($research_details_field->get('field_research_focuses') as $focus) {
            $research['focuses'][] = $focus->get('entity')->getValue()->toLink()->getText();
          }
        }
        if ($research_details_field->get('field_research_organisms')->count()) {
          $research['organisms'] = [];
          foreach ($research_details_field->get('field_research_organisms') as $organism) {
            $research['organisms'][] = $organism->get('entity')->getValue()->toLink()->getText();
          }
        }

        if (!empty($research)) {
          $response['research'] = $research;
        }
      }

      $response = new JsonResponse($response, Response::HTTP_OK, ['Content-Type' => 'application/vnd.elife.person+json;version=1']);
      return $response;
    }

    throw new NotFoundHttpException(t('Person with ID @id was not found', ['@id' => $id]));
  }

}
