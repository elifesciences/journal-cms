<?php

namespace Drupal\jcms_rest\Plugin\rest\resource;

use Drupal\jcms_rest\Exception\JCMSNotFoundHttpException;
use Drupal\jcms_rest\Response\JCMSRestResponse;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "labs_experiment_item_rest_resource",
 *   label = @Translation("Labs post item rest resource"),
 *   uri_paths = {
 *     "canonical" = "/labs-posts/{id}"
 *   }
 * )
 */
class LabsExperimentItemRestResource extends AbstractRestResourceBase {

  /**
   * Latest version.
   *
   * @var int
   */
  protected $latestVersion = 2;

  /**
   * Responds to GET requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @throws \Drupal\jcms_rest\Exception\JCMSNotFoundHttpException
   */
  public function get(string $id) : JCMSRestResponse {
    if ($this->checkId($id)) {
      $query = \Drupal::entityQuery('node')
        ->accessCheck(TRUE)
        ->condition('type', 'labs_experiment')
        ->condition('uuid', '%' . $id, 'LIKE');

      if (!$this->viewUnpublished()) {
        $query->condition('status', NodeInterface::PUBLISHED);
      }

      $nids = $query->execute();
      if ($nids) {
        $nid = reset($nids);
        /** @var \Drupal\node\Entity\Node $node */
        $node = Node::load($nid);

        $this->setSortBy('created', TRUE);
        $response = $this->processDefault($node);

        // Image is required.
        $response['image'] = $this->processFieldImage($node->get('field_image'), TRUE, 'thumbnail');
        $attribution = $this->fieldValueFormatted($node->get('field_image_attribution'), FALSE, TRUE);
        if (!empty($attribution)) {
          foreach ($response['image'] as $key => $type) {
            $response['image'][$key]['attribution'] = $attribution;
          }
        }

        // Social image is optional.
        if ($socialImage = $this->processFieldImage($node->get('field_image_social'), FALSE, 'social', TRUE)) {
          $response['image']['social'] = $socialImage;
        }

        // Impact statement is optional.
        if ($node->get('field_impact_statement')->count()) {
          $response['impactStatement'] = $this->fieldValueFormatted($node->get('field_impact_statement'));
          if (empty($response['impactStatement'])) {
            unset($response['impactStatement']);
          }
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

    throw new JCMSNotFoundHttpException(t('Lab experiment with ID @id was not found', ['@id' => $id]));
  }

}
