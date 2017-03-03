<?php

namespace Drupal\jcms_rest\Plugin\rest\resource;

use Drupal\jcms_rest\Exception\JCMSNotFoundHttpException;
use Drupal\jcms_rest\Response\JCMSRestResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "person_item_rest_resource",
 *   label = @Translation("Person rest resource"),
 *   uri_paths = {
 *     "canonical" = "/people/{id}"
 *   }
 * )
 */
class PersonItemRestResource extends AbstractRestResourceBase {
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
        'type' => $node->get('field_person_type')->getString(),
        'name' => [
          'preferred' => $node->getTitle(),
          'index' => $node->get('field_person_index_name')->getString(),
        ],
      ];

      // Orcid is optional.
      if ($node->get('field_person_orcid')->count()) {
        $item['orcid'] = $node->get('field_person_orcid')->getString();
      }

      // Image is optional.
      if ($image = $this->processFieldImage($node->get('field_image'), FALSE, 'thumbnail', TRUE)) {
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
          $expertises = [
            'id' => [],
            'name' => [],
          ];
          foreach ($research_details_field->get('field_research_expertises') as $expertise) {
            $expertise_id = $expertise->get('entity')->getValue()->get('field_subject_id')->getString();
            $expertise_name = $expertise->get('entity')->getValue()->toLink()->getText();
            if (!in_array($expertise_id, $expertises['id']) && !in_array($expertise_name, $expertises['name'])) {
              $research['expertises'][] = [
                'id' => $expertise_id,
                'name' => $expertise_name,
              ];
              $expertises['id'][] = $expertise_id;
              $expertises['name'][] = $expertise_name;
            }
          }
        }
        if ($research_details_field->get('field_research_focuses')->count()) {
          $research['focuses'] = [];
          foreach ($research_details_field->get('field_research_focuses') as $focus) {
            $focus_text = $focus->get('entity')->getValue()->toLink()->getText();
            if (in_array($focus_text, $research['focuses'])) {
              $research['focuses'][] = $focus_text;
            }
          }
        }
        if ($research_details_field->get('field_research_organisms')->count()) {
          $research['organisms'] = [];
          foreach ($research_details_field->get('field_research_organisms') as $organism) {
            $organism_text = $organism->get('entity')->getValue()->toLink()->getText();
            if (in_array($organism_text, $research['organisms'])) {
              $research['organisms'][] = $organism_text;
            }
          }
        }

        if (!empty($research)) {
          $response['research'] = $research;
        }
      }

      $response = new JCMSRestResponse($response, Response::HTTP_OK, ['Content-Type' => $this->getContentType()]);
      $response->addCacheableDependency($node);
      return $response;
    }

    throw new JCMSNotFoundHttpException(t('Person with ID @id was not found', ['@id' => $id]));
  }

}
