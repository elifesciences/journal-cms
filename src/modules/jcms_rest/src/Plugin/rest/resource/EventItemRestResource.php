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
 *   id = "event_item_rest_resource",
 *   label = @Translation("Event item rest resource"),
 *   uri_paths = {
 *     "canonical" = "/events/{id}"
 *   }
 * )
 */
class EventItemRestResource extends AbstractRestResourceBase {
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
        ->condition('type', 'event')
        ->condition('uuid', '%' . $id, 'LIKE');

      if (!$this->viewUnpublished()) {
        $query->condition('status', NodeInterface::PUBLISHED);
      }

      $nids = $query->execute();
      if ($nids) {
        $nid = reset($nids);
        /** @var \Drupal\node\Entity\Node $node */
        $node = Node::load($nid);

        $this->setSortBy(FALSE);
        $response = $this->processDefault($node, $id);

        $response['starts'] = $this->formatDate(strtotime($node->get('field_event_datetime')->first()->getValue()['value']));
        $response['ends'] = $this->formatDate(strtotime($node->get('field_event_datetime')->first()->getValue()['end_value']));

        // Timezone is optional.
        if ($node->get('field_event_timezone')->count()) {
          $response['timezone'] = $node->get('field_event_timezone')->getString();
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

        // URI is optional.
        if ($node->get('field_event_uri')->count()) {
          $response['uri'] = $node->get('field_event_uri')->first()->getValue()['uri'];
        }
        // Content is optional, only display if there is no Event URI.
        elseif (!$this->viewUnpublished() && $content = $node->get('field_content_json')->getString()) {
          $response['content'] = json_decode($node->get('field_content_json')->getString());
        }
        elseif ($this->viewUnpublished() && $content = $node->get('field_content_json')->getString()) {
          $response['content'] = json_decode($node->get('field_content_json_preview')->getString());
        }

        $response = new JCMSRestResponse($response, Response::HTTP_OK, ['Content-Type' => $this->getContentType()]);
        $response->addCacheableDependency($node);
        $this->processResponse($response);
        return $response;
      }
    }

    throw new JCMSNotFoundHttpException(t('Event with ID @id was not found', ['@id' => $id]));
  }

}
