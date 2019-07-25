<?php

namespace Drupal\jcms_ckeditor\Plugin\rest\resource;

use Drupal\node\Entity\Node;
use eLife\ApiValidator\Exception\InvalidMessage;
use Drupal\jcms_rest\Plugin\rest\resource\AbstractRestResourceBase;
use Drupal\jcms_rest\Response\JCMSRestResponse;
use Drupal\jcms_rest\Exception\JCMSNotFoundHttpException;
use Symfony\Component\HttpFoundation\Response;

const VALIDATE_ACTION = 1;
const PUBLISH_ACTION = 2;
const VALIDATE_AND_PUBLISH_ACTION = 3;

/**
* Provides an rest resource to validate and publish content
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
          
  /**
   * Responds to GET requests.
   * 
   * Returns a indication of whether current preview content for given
   * node validates. If it does it is published.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function get(string $action, string $id) {
    
    $response['validated'] = FALSE;
    $response['published'] = FALSE;
    if ($this->checkId($id)) {
      $query = \Drupal::entityQuery('node')
        ->condition('changed', \Drupal::time()->getRequestTime(), '<')
        ->condition('uuid', '%' . $id, 'LIKE');
      
      $nids = $query->execute();
      if ($nids) {
        $nid = reset($nids);
        /* @var \Drupal\node\Entity\Node $node */
        $node = Node::load($nid);
        $validator = \Drupal::service('jcms_rest.content_validator');
        
        try {
          if ($action == VALIDATE_ACTION || $action == VALIDATE_AND_PUBLISH_ACTION) {
            $json = $validator->validate($node, TRUE);
            $response['validated'] = TRUE;
          }
          if ($action == PUBLISH_ACTION || $action == VALIDATE_AND_PUBLISH_ACTION) {
            // Save and publish node
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
