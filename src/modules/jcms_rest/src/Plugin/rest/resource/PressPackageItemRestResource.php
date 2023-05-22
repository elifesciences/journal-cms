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
 *   id = "press_package_item_rest_resource",
 *   label = @Translation("Press package item rest resource"),
 *   uri_paths = {
 *     "canonical" = "/press-packages/{id}"
 *   }
 * )
 */
class PressPackageItemRestResource extends AbstractRestResourceBase {

  /**
   * Latest version.
   *
   * @var int
   */
  protected $latestVersion = 4;

  /**
   * Minimum version.
   *
   * @var int
   */
  protected $minVersion = 2;

  /**
   * Responds to GET requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @throws \Drupal\jcms_rest\Exception\JCMSNotFoundHttpException
   */
  public function get(string $id) : JCMSRestResponse {
    if ($this->checkId($id)) {
      $query = \Drupal::entityQuery('node')
        ->accessCheck(TRUE)
        ->condition('type', 'press_package')
        ->condition('uuid', '%' . $id, 'LIKE');

      if (!$this->viewUnpublished()) {
        $query->condition('status', NodeInterface::PUBLISHED);
      }

      $nids = $query->execute();
      if ($nids) {
        $nid = reset($nids);
        $node = Node::load($nid);

        $response = $this->processDefault($node, $id);

        // Social image is optional.
        if ($socialImage = $this->processFieldImage($node->get('field_image_social'), FALSE, 'social', TRUE)) {
          $response['image']['social'] = $socialImage;
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

        if ($node->get('field_related_content')->count()) {
          $related_content = [];
          foreach ($node->get('field_related_content')->referencedEntities() as $related) {
            if ($article = $this->getArticleSnippet($related, $this->acceptVersion >= 4)) {
              $related_content[] = $article;
            }
          }
          if (!empty($related_content)) {
            $response['relatedContent'] = $related_content;
          }
        }

        // Subjects is optional.
        $subjects = $this->subjectsFromArticles($response['relatedContent']);
        if (!empty($subjects)) {
          $response['subjects'] = $subjects;
        }

        // @todo elife - nlisgo - expose this in a form in admin UI.
        $response['mediaContacts'] = [
          [
            'name' => [
              'preferred' => 'Emily Packer',
              'index' => 'Packer, Emily',
            ],
            'emailAddresses' => [
              'e.packer@elifesciences.org',
            ],
            'phoneNumbers' => [
              '+441223855373',
            ],
            'affiliations' => [
              [
                'name' => [
                  'eLife',
                ],
              ],
            ],
          ],
        ];
        if ($node->get('field_media_contact')->count()) {
          foreach ($node->get('field_media_contact') as $media_contact) {
            $media_contact_item = $media_contact->get('entity')->getTarget()->getValue();
            $media_contact_values['name'] = $this->processPeopleNames($media_contact_item->get('field_block_preferred_name')->getString(), $media_contact_item->get('field_block_index_name'));

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

        $response = new JCMSRestResponse($response, Response::HTTP_OK, ['Content-Type' => $this->getContentType()]);
        $response->addCacheableDependency($node);
        $this->processResponse($response);
        return $response;
      }
    }

    throw new JCMSNotFoundHttpException(t('Blog article with ID @id was not found', ['@id' => $id]));
  }

}
