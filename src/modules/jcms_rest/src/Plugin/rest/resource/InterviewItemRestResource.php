<?php

namespace Drupal\jcms_rest\Plugin\rest\resource;

use Drupal\node\Entity\Node;
use Drupal\jcms_rest\Exception\JCMSNotFoundHttpException;
use Drupal\jcms_rest\Response\JCMSRestResponse;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "interview_item_rest_resource",
 *   label = @Translation("Interview item rest resource"),
 *   uri_paths = {
 *     "canonical" = "/interviews/{id}"
 *   }
 * )
 */
class InterviewItemRestResource extends AbstractRestResourceBase {
  protected $latestVersion = 2;

  /**
   * Responds to GET requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @throws JCMSNotFoundHttpException
   */
  public function get(string $id) : JCMSRestResponse {
    if ($this->checkId($id)) {
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'interview')
        ->condition('uuid', '%' . $id, 'LIKE');

      if (!$this->viewUnpublished()) {
        $query->condition('status', NodeInterface::PUBLISHED);
      }

      $nids = $query->execute();
      if ($nids) {
        $nid = reset($nids);
        /* @var \Drupal\node\Entity\Node $node */
        $node = Node::load($nid);

        $response = $this->processDefault($node, $id);

        $response['interviewee']['name'] = $this->processPeopleNames($node->get('field_person_preferred_name')->getString(), $node->get('field_person_index_name'));

        // Impact statement is optional.
        if ($node->get('field_impact_statement')->count()) {
          $response['impactStatement'] = $this->fieldValueFormatted($node->get('field_impact_statement'));
          if (empty($response['impactStatement'])) {
            unset($response['impactStatement']);
          }
        }

        // Image is optional.
        if ($image = $this->processFieldImage($node->get('field_image'), FALSE, 'thumbnail')) {
          $response['image'] = $image;
        }

        if (!$this->viewUnpublished()) {
          $response['content'] = json_decode($node->get('field_content_json')->getString());
        }
        else {
          $response['content'] = json_decode($node->get('field_content_json_preview')->getString());
        }

        $response = new JCMSRestResponse($response, Response::HTTP_OK, ['Content-Type' => $this->getContentType()]);
        $response->addCacheableDependency($node);
        $this->processResponse($response);
        return $response;
      }
    }

    throw new JCMSNotFoundHttpException(t('Interview with ID @id was not found', ['@id' => $id]));
  }

}
