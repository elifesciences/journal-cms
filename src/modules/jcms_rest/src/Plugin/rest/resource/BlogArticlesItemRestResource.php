<?php

namespace Drupal\jcms_rest\Plugin\rest\resource;

use Drupal\jcms_rest\Exception\JCMSNotFoundHttpException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "blog_articles_item_rest_resource",
 *   label = @Translation("Blog articles item rest resource"),
 *   uri_paths = {
 *     "canonical" = "/blog-articles/{id}"
 *   }
 * )
 */
class BlogArticlesItemRestResource extends AbstractRestResourceBase {
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
      if ($image = $this->processFieldImage($node->get('field_image'))) {
        $response['image'] = $image;
      }

      // Impact statement is optional.
      if ($node->get('field_impact_statement')->count()) {
        $response['impactStatement'] = $node->get('field_impact_statement')->first()->getValue()['value'];
      }

      if ($content = $this->processFieldContent($node->get('field_content'))) {
        $response['content'] = $content;
      }

      // Subjects is optional.
      $subjects = $this->processSubjects($node->get('field_subjects'));
      if (!empty($subjects)) {
        $response['subjects'] = $subjects;
      }

      $response = new JsonResponse($response, Response::HTTP_OK, ['Content-Type' => 'application/vnd.elife.blog-article+json;version=1']);
      return $response;
    }
    throw new JCMSNotFoundHttpException(t('Blog article with ID @id was not found', ['@id' => $id]), NULL, 'application/problem+json');
  }

}
