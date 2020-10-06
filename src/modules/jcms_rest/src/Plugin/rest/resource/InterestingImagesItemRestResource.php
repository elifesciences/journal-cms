<?php

namespace Drupal\jcms_rest\Plugin\rest\resource;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\jcms_rest\Exception\JCMSBadRequestHttpException;
use Drupal\jcms_rest\Exception\JCMSNotFoundHttpException;
use Drupal\jcms_rest\Response\JCMSRestResponse;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "interesting_images_item_rest_resource",
 *   label = @Translation("Interesting images item rest resource"),
 *   uri_paths = {
 *     "canonical" = "/interesting-images/{type}/{id}"
 *   }
 * )
 */
class InterestingImagesItemRestResource extends AbstractRestResourceBase {
  const TYPES = [
    'article' => 'article',
  ];

  /**
   * Responds to GET requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @throws JCMSNotFoundHttpException
   */
  public function get(string $type, string $id) : JCMSRestResponse {
    if (!in_array($type, array_keys(self::TYPES))) {
      throw new JCMSBadRequestHttpException(t('Invalid type'));
    }
    if ($this->checkId($id, $type)) {
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'job_advert')
        ->condition('uuid', '%' . $id, 'LIKE');

      if (!$this->viewUnpublished()) {
        $query->condition('status', NodeInterface::PUBLISHED);
      }

      $nids = $query->execute();
      if ($nids) {
        $nid = reset($nids);
        /* @var Node $node */
        $node = Node::load($nid);

        $this->setSortBy(FALSE);
        $response = $this->processDefault($node, $id);

        // Impact statement is optional.
        if ($node->get('field_impact_statement')->count()) {
          $response['impactStatement'] = $this->fieldValueFormatted($node->get('field_impact_statement'));
          if (empty($response['impactStatement'])) {
            unset($response['impactStatement']);
          }
        }

        $response['closingDate'] = $this->formatDate($node->get('field_job_advert_closing_date')->first()->getValue()['value']);
        $response['content'] = $this->deriveContentJson($node);

        $response = new JCMSRestResponse($response, Response::HTTP_OK, ['Content-Type' => $this->getContentType()]);
        $response->addCacheableDependency($node);
        $this->processResponse($response);
        return $response;
      }
    }

    throw new JCMSNotFoundHttpException(t('Job advert with ID @id was not found', ['@id' => $id]));
  }

}
