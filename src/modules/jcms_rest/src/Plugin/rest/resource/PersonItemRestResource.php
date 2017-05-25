<?php

namespace Drupal\jcms_rest\Plugin\rest\resource;

use Drupal\jcms_rest\Exception\JCMSNotFoundHttpException;
use Drupal\jcms_rest\Response\JCMSRestResponse;
use Drupal\node\Entity\Node;
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
    $response = $this->getItemResponse($id);
    if ($response instanceof Response) {
      return $response;
    }

    throw new JCMSNotFoundHttpException(t('Person with ID @id was not found', ['@id' => $id]));
  }

  /**
   * Get item response
   *
   * @param string $id
   * @return Response|bool
   */
  public function getItemResponse($id) {
    $query = \Drupal::entityQuery('node')
      ->condition('status', \Drupal\node\NodeInterface::PUBLISHED)
      ->condition('changed', \Drupal::time()->getRequestTime(), '<')
      ->condition('field_archive.value', 0)
      ->condition('type', 'person')
      ->condition('uuid', '%' . $id, 'LIKE');

    // @todo - elife - nlisgo - Handle version specific requests
    // @todo - elife - nlisgo - Handle content negotiation

    $nids = $query->execute();
    if ($nids) {
      $nid = reset($nids);
      $node = Node::load($nid);
      $item = $this->getItem($node);
      $response = new JCMSRestResponse($item, Response::HTTP_OK, ['Content-Type' => $this->getContentType()]);
      $response->addCacheableDependency($node);
      return $response;
    }

    \Drupal::logger('jcms_rest')->warning('No item response found for person with id.', ['id' => $id]);
    return FALSE;
  }

  /**
   * Takes a node and builds an item from it.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node
   *
   * @return array
   */
  public function getItem($node) {
    $person_list_rest_resource = new PersonListRestResource([], 'person_list_rest_resource', [], $this->serializerFormats, $this->logger);
    $item = $person_list_rest_resource->getItem($node);

    // Profile description is optional.
    if ($profile = $this->processFieldContent($node->get('field_person_profile'))) {
      $item['profile'] = $profile;
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
          if (!in_array($focus_text, $research['focuses'])) {
            $research['focuses'][] = $focus_text;
          }
        }
      }
      if ($research_details_field->get('field_research_organisms')->count()) {
        $research['organisms'] = [];
        foreach ($research_details_field->get('field_research_organisms') as $organism) {
          $organism_text = $organism->get('entity')->getValue()->toLink()->getText();
          if (!in_array($organism_text, $research['organisms'])) {
            $research['organisms'][] = $organism_text;
          }
        }
      }

      if (!empty($research)) {
        $item['research'] = $research;
      }
    }

    if ($node->get('field_person_affiliation')->count()) {
      $countries = \Drupal::service('country_manager')->getList();
      $affiliations = [];
      foreach ($node->get('field_person_affiliation') as $affiliation) {
        $data = $affiliation->get('entity')->getTarget()->getValue();
        $country = $data->get('field_block_country')->getString();
        $affiliations[] = [
          'name' => explode("\n", $data->get('field_block_title_multiline')->getString()),
          'address' => [
            'formatted' => [$countries[$country]],
            'components' => ['country' => $countries[$country]],
          ],
        ];
      }

      $item['affiliations'] = $affiliations;
    }

    return $item;
  }

}
