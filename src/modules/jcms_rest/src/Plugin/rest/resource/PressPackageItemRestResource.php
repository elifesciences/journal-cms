<?php

namespace Drupal\jcms_rest\Plugin\rest\resource;

use Drupal\jcms_rest\Exception\JCMSNotFoundHttpException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "press_package_item_rest_resource",
 *   label = @Translation("Press package item rest resource"),
 *   uri_paths = {
 *     "canonical" = "/press-packages/{id}"
 *   }
 * )
 */
class PressPackageItemRestResource extends AbstractRestResourceBase {
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
      ->condition('type', 'press_package')
      ->condition('uuid', '%' . $id, 'LIKE');

    $nids = $query->execute();
    if ($nids) {
      $nid = reset($nids);
      /* @var \Drupal\node\Entity\Node $node */
      $node = \Drupal\node\Entity\Node::load($nid);

      $response = $this->processDefault($node, $id);

      // Impact statement is optional.
      if ($node->get('field_impact_statement')->count()) {
        $response['impactStatement'] = $this->fieldValueFormatted($node->get('field_impact_statement'));
      }

      if ($content = $this->processFieldContent($node->get('field_content'))) {
        $response['content'] = $content;
      }

      $related_content = [];
      foreach ($node->get('field_related_content') as $related) {
        if ($article = $this->getArticleSnippet($related->get('entity')->getTarget()->getValue())) {
          $related_content[] = $article;
        }
      }
      if (!empty($related_content)) {
        $response['relatedContent'] = $related_content;
      }

      // Subjects is optional.
      $subjects = $this->subjectsFromArticles($response['relatedContent']);
      if (!empty($subjects)) {
        $response['subjects'] = $subjects;
      }

      if ($node->get('field_media_contact')->count()) {
        $response['mediaContacts'] = [];
        foreach ($node->get('field_media_contact') as $media_contact) {
          $media_contact_item = $media_contact->get('entity')->getTarget()->getValue();
          $media_contact_values = [
            'name' => [
              'preferred' => $media_contact_item->get('field_block_preferred_name')->getString(),
              'index' => $media_contact_item->get('field_block_index_name')->getString(),
            ],
          ];

          // Media contact email is optional.
          if ($media_contact_item->get('field_block_email')->count()) {
            $media_contact_values['emailAddresses'] = [];
            foreach ($media_contact_item->get('field_block_email') as $email) {
              $media_contact_values['emailAddresses'][] = $email->getString();
            }
          }

          // Media contact phone number is optional.
          if ($media_contact_item->get('field_block_phone_number')->count()) {
            $media_contact_values['phoneNumbers'] = [];
            foreach ($media_contact_item->get('field_block_phone_number') as $phone_number) {
              $media_contact_values['phoneNumbers'][] = $phone_number->getString();
            }
          }

          // Media contact affiliation is optional.
          if ($media_contact_item->get('field_block_affiliation')->count()) {
            $media_contact_values['affiliations'] = [$this->getVenue($media_contact_item->get('field_block_affiliation')->first()->get('entity')->getTarget()->getValue())];
          }

          $response['mediaContacts'][] = $media_contact_values;
        }
      }

      if ($about = $this->processFieldContent($node->get('field_press_package_about'))) {
        $response['about'] = $about;
      }

      $response = new JsonResponse($response, Response::HTTP_OK, ['Content-Type' => 'application/vnd.elife.press-package+json;version=1']);
      return $response;
    }
    throw new JCMSNotFoundHttpException(t('Blog article with ID @id was not found', ['@id' => $id]), NULL, 'application/problem+json');
  }

}
