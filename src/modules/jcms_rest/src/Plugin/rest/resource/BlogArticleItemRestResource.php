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
 *   id = "blog_article_item_rest_resource",
 *   label = @Translation("Blog article item rest resource"),
 *   uri_paths = {
 *     "canonical" = "/blog-articles/{id}"
 *   }
 * )
 */
class BlogArticleItemRestResource extends AbstractRestResourceBase {
  protected $latestVersion = 2;

  /**
   * Responds to GET requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function get(string $id) : JCMSRestResponse {
    if ($this->checkId($id)) {
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'blog_article')
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

        // Image is optional.
        if ($image = $this->processFieldImage($node->get('field_image'), FALSE)) {
          $attribution = $this->fieldValueFormatted($node->get('field_image_attribution'), FALSE, TRUE);
          if (!empty($attribution)) {
            foreach ($image as $key => $type) {
              $image[$key]['attribution'] = $attribution;
            }
          }
          $response['image'] = $image;
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

        // Subjects is optional.
        $subjects = $this->processSubjects($node->get('field_subjects'));
        if (!empty($subjects)) {
          $response['subjects'] = $subjects;
        }

        $response = new JCMSRestResponse($response, Response::HTTP_OK, ['Content-Type' => $this->getContentType()]);
        $response->addCacheableDependency($node);
        $this->processResponse($response);
        return $response;
      }
    }

    throw new JCMSNotFoundHttpException(t('Blog article with ID @id was not found', ['@id' => $id]));
  }

}
