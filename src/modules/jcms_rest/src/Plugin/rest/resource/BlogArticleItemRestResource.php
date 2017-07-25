<?php

namespace Drupal\jcms_rest\Plugin\rest\resource;

use Drupal\jcms_rest\Exception\JCMSNotFoundHttpException;
use Drupal\jcms_rest\Response\JCMSRestResponse;
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
      ->condition('type', 'blog_article')
      ->condition('uuid', '%' . $id, 'LIKE');

    $nids = $query->execute();
    if ($nids) {
      $nid = reset($nids);
      /* @var \Drupal\node\Entity\Node $node */
      $node = \Drupal\node\Entity\Node::load($nid);

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

      if ($content = $this->processFieldContent($node->get('field_content'))) {
        $response['content'] = $content;
      }

      // Subjects is optional.
      $subjects = $this->processSubjects($node->get('field_subjects'));
      if (!empty($subjects)) {
        $response['subjects'] = $subjects;
      }

      $response = new JCMSRestResponse($response, Response::HTTP_OK, ['Content-Type' => $this->getContentType()]);
      $response->addCacheableDependency($node);
      return $response;
    }
    throw new JCMSNotFoundHttpException(t('Blog article with ID @id was not found', ['@id' => $id]));
  }

}
