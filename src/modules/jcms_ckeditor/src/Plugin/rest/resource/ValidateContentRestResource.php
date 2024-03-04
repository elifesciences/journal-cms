<?php

namespace Drupal\jcms_ckeditor\Plugin\rest\resource;

use Drupal\jcms_rest\Exception\JCMSNotFoundHttpException;
use Drupal\jcms_rest\Plugin\rest\resource\AbstractRestResourceBase;
use Drupal\jcms_rest\Response\JCMSRestResponse;
use Drupal\node\Entity\Node;
use eLife\ApiValidator\Exception\InvalidMessage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides an rest resource to validate and publish content.
 *
 * @RestResource(
 *   id = "validate_content_rest_resource",
 *   label = @Translation("Validate and publish content rest resource"),
 *   uri_paths = {
 *     "canonical" = "/validate-publish/{action}/{id}",
 *   }
 * )
 */
class ValidateContentRestResource extends AbstractRestResourceBase {

  const VALIDATE_ACTION = 1;
  const PUBLISH_ACTION = 2;
  const VALIDATE_AND_PUBLISH_ACTION = 3;

  /**
   * Responds to GET requests.
   *
   * Returns a indication of whether current preview content for given
   * node validates. If it does it is published.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Description.
   */
  public function get(int $action, string $id) {
    $response['validated'] = FALSE;
    $response['published'] = FALSE;
    if ($this->checkId($id)) {
      $query = \Drupal::entityQuery('node')
        ->accessCheck(TRUE)
        ->condition('uuid', '%' . $id, 'LIKE');

      $nids = $query->execute();
      if ($nids) {
        $nid = reset($nids);
        /** @var \Drupal\node\Entity\Node $node */
        $node = Node::load($nid);
        $validator = \Drupal::service('jcms_rest.content_validator');
        $host = \Drupal::request()->getSchemeAndHttpHost();
        $validator->setBaseUrl($host);

        try {
          if ($action === self::VALIDATE_ACTION || $action === self::VALIDATE_AND_PUBLISH_ACTION) {
            $validator->validate($node, TRUE);
            $response['validated'] = TRUE;
          }
          if ($action === self::PUBLISH_ACTION || $action === self::VALIDATE_AND_PUBLISH_ACTION) {
            // Save and publish node.
            _jcms_admin_static_store('ckeditor_transfer_content_' . $node->id(), TRUE);
            $node->save();
            $response['published'] = TRUE;
          }
        }
        catch (InvalidMessage $message) {

        }
      }

      $response = new JCMSRestResponse($response, Response::HTTP_OK);
      $response->addCacheableDependency($node);
      $this->processResponse($response);
      return $response;
    }

    throw new JCMSNotFoundHttpException(t('Node with ID @id was not found so could not be validated', ['@id' => $id]));
  }

}
