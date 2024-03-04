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
 *   id = "annual_report_item_rest_resource",
 *   label = @Translation("Annual report item rest resource"),
 *   uri_paths = {
 *     "canonical" = "/annual-reports/{year}"
 *   }
 * )
 */
class AnnualReportItemRestResource extends AbstractRestResourceBase {

  /**
   * Latest version.
   *
   * @var int
   */
  protected $latestVersion = 2;

  /**
   * Minimum version.
   *
   * @var int
   */
  protected $minVersion = 2;

  /**
   * Responds to GET requests.
   *
   * @throws \Drupal\jcms_rest\Exception\JCMSNotFoundHttpException
   */
  public function get(int $year) : JCMSRestResponse {
    $query = \Drupal::entityQuery('node')
      ->accessCheck(TRUE)
      ->condition('type', 'annual_report')
      ->condition('field_annual_report_year.value', $year);

    if (!$this->viewUnpublished()) {
      $query->condition('status', NodeInterface::PUBLISHED);
    }

    $nids = $query->execute();
    if ($nids) {
      $nid = reset($nids);
      /** @var \Drupal\node\Entity\Node $node */
      $node = Node::load($nid);

      $this->setSortBy(FALSE);
      $response = $this->processDefault($node, $year, 'year');

      // Uri is required.
      $response['uri'] = $node->get('field_annual_report_uri')->first()->getValue()['uri'];

      // PDF is optional.
      if ($node->get('field_pdf')->count()) {
        $response['pdf'] = $node->get('field_pdf')->first()->getValue()['uri'];
      }

      // Impact statement is optional.
      if ($node->get('field_impact_statement')->count()) {
        $response['impactStatement'] = $this->fieldValueFormatted($node->get('field_impact_statement'));
        if (empty($response['impactStatement'])) {
          unset($response['impactStatement']);
        }
      }

      $response = new JCMSRestResponse($response, Response::HTTP_OK, ['Content-Type' => $this->getContentType()]);
      $response->addCacheableDependency($node);
      $this->processResponse($response);
      return $response;
    }

    throw new JCMSNotFoundHttpException(t('Annual report with ID @id was not found', ['@id' => $year]));
  }

}
