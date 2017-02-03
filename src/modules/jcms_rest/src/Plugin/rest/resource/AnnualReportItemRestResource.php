<?php

namespace Drupal\jcms_rest\Plugin\rest\resource;

use Drupal\jcms_rest\Exception\JCMSNotFoundHttpException;
use Symfony\Component\HttpFoundation\JsonResponse;
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
   * Responds to GET requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @param int $year
   * @return array|\Symfony\Component\HttpFoundation\JsonResponse
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function get(int $year) {
    $query = \Drupal::entityQuery('node')
      ->condition('status', NODE_PUBLISHED)
      ->condition('changed', REQUEST_TIME, '<')
      ->condition('type', 'annual_report')
      ->condition('field_annual_report_year.value', $year);

    $nids = $query->execute();
    if ($nids) {
      $nid = reset($nids);
      /* @var \Drupal\node\Entity\Node $node */
      $node = \Drupal\node\Entity\Node::load($nid);

      $this->setSortBy(FALSE);
      $response = $this->processDefault($node, $year, 'year');

      // uri is required.
      $response['uri'] = $node->get('field_annual_report_uri')->first()->getValue()['uri'];

      // Impact statement is optional.
      if ($node->get('field_impact_statement')->count()) {
        $response['impactStatement'] = $this->fieldValueFormatted($node->get('field_impact_statement'));
      }

      // Image is required.
      $response['image'] = $this->processFieldImage($node->get('field_image'), FALSE, 'thumbnail', TRUE);

      $response = new JsonResponse($response, Response::HTTP_OK, ['Content-Type' => 'application/vnd.elife.annual-report+json;version=1']);
      return $response;
    }

    throw new JCMSNotFoundHttpException(t('Annual report with ID @id was not found', ['@id' => $year]));
  }

}
