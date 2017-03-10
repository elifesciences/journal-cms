<?php

namespace Drupal\jcms_rest\Plugin\rest\resource;

use Drupal\jcms_rest\Exception\JCMSNotFoundHttpException;
use Drupal\jcms_rest\Response\JCMSRestResponse;
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
  /**
   * Responds to GET requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @param string $id
   * @return array|\Symfony\Component\HttpFoundation\JsonResponse
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function get($id) {
    $query = \Drupal::entityQuery('node')
      ->condition('status', NODE_PUBLISHED)
      ->condition('changed', REQUEST_TIME, '<')
      ->condition('type', 'event')
      ->condition('uuid', '%' . $id, 'LIKE');

    $nids = $query->execute();
    if ($nids) {
      $nid = reset($nids);
      /* @var \Drupal\node\Entity\Node $node */
      $node = \Drupal\node\Entity\Node::load($nid);

      $this->setSortBy(FALSE);
      $response = $this->processDefault($node, $id);

      $response['starts'] = $this->formatDate(strtotime($node->get('field_event_datetime')->first()->getValue()['value']));
      $response['ends'] = $this->formatDate(strtotime($node->get('field_event_datetime')->first()->getValue()['end_value']));

      // Timezone is optional.
      if ($node->get('field_event_timezone')->count()) {
        $response['timezone'] = $node->get('field_event_timezone')->getString();
      }

      // Impact statement is optional.
      if ($node->get('field_impact_statement')->count()) {
        $response['impactStatement'] = $this->fieldValueFormatted($node->get('field_impact_statement'));
      }

      // URI is optional.
      if ($node->get('field_event_uri')->count()) {
        $response['uri'] = $node->get('field_event_uri')->first()->getValue()['uri'];
      }
      // Content is optional, only display if there is no Event URI.
      elseif ($content = $this->processFieldContent($node->get('field_content'))) {
        $response['content'] = $content;
      }

      $response = new JCMSRestResponse($response, Response::HTTP_OK, ['Content-Type' => $this->getContentType()]);
      $response->addCacheableDependency($node);
      return $response;
    }

    throw new JCMSNotFoundHttpException(t('Event with ID @id was not found', ['@id' => $id]));
  }

}
