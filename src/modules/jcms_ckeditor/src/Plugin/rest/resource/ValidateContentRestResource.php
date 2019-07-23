<?php

namespace Drupal\jcms_ckeditor\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Drupal\node\Entity\Node;
use eLife\ApiValidator\Exception\InvalidMessage;
use Drupal\jcms_rest\Plugin\rest\resource\AbstractRestResourceBase;

/**
* Provides an rest resource to validate content
*
* @RestResource(
*   id = "validate_content_rest_resource",
*   label = @Translation("Validate content rest resource"),
*   uri_paths = {
*     "canonical" = "/validate/{id}",
*   }
* )
*/
class ValidateContentRestResource extends AbstractRestResourceBase {
          
  /**
   * Responds to GET requests.
   *
   * @throws JCMSNotFoundHttpException
   */
  public function get(string $id) {
    
    $validated = FALSE;
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
          $json = $validator->validate($node, TRUE);
          $validated = TRUE;
        }
        catch (InvalidMessage $message) {
          
        }
      }
    }
    
    // Configure caching settings.
    $build = [
      '#cache' => [
        'max-age' => 0,
      ],
    ];

    return (new ResourceResponse(['validated' => $validated], 200))->addCacheableDependency($build);
  
  }
  
}