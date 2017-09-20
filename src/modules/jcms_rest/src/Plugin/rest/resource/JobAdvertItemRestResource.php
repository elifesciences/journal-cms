<?php

namespace Drupal\jcms_rest\Plugin\rest\resource;

use Drupal\jcms_rest\Exception\JCMSNotFoundHttpException;
use Drupal\jcms_rest\Response\JCMSRestResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "job_advert_item_rest_resource",
 *   label = @Translation("Job advert item rest resource"),
 *   uri_paths = {
 *     "canonical" = "/job-adverts/{id}"
 *   }
 * )
 */
class JobAdvertItemRestResource extends AbstractRestResourceBase {
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
      ->condition('status', \Drupal\node\NodeInterface::PUBLISHED)
      ->condition('changed', \Drupal::time()->getRequestTime(), '<')
      ->condition('type', 'job_advert')
      ->condition('uuid', '%' . $id, 'LIKE');

    $nids = $query->execute();
    if ($nids) {
      $nid = reset($nids);
      /* @var \Drupal\node\Entity\Node $node */
      $node = \Drupal\node\Entity\Node::load($nid);

      $this->setSortBy(FALSE);
      $response = $this->processDefault($node, $id);

      // Impact statement is optional.
      if ($node->get('field_impact_statement')->count()) {
        $response['impactStatement'] = $this->fieldValueFormatted($node->get('field_impact_statement'));
        if (empty($response['impactStatement'])) {
          unset($response['impactStatement']);
        }
      }

      $response['content'] = $this->deriveContentJson($node);

      $response = new JCMSRestResponse($response, Response::HTTP_OK, ['Content-Type' => $this->getContentType()]);
      $response->addCacheableDependency($node);
      return $response;
    }

    throw new JCMSNotFoundHttpException(t('Job advert with ID @id was not found', ['@id' => $id]));
  }

  /**
   * @param \Drupal\node\Entity\Node $node
   * @return array
   */
  private function deriveContentJson($node) {
    $contentJson = [];
    $fieldsData = [
      [
        'name' => 'field_job_advert_role_summary',
        'isSection' => false,
      ],
      [
        'name' => 'field_job_advert_experience',
        'isSection' => true,
      ],
      [
        'name' => 'field_job_advert_respons',
        'isSection' => true,
      ],
      [
        'name' => 'field_job_advert_terms',
        'isSection' => true,
      ],
    ];

    foreach($fieldsData as $fieldData) {
      $field = $node->get($fieldData['name']);
      if($field->count()) {
        $fieldLabel = $node->{$fieldData['name']}->getFieldDefinition()->getLabel();
        if ($fieldData['isSection']) {
          array_push($contentJson, $this->getFieldJson($field, $fieldLabel, true));
        } else {
          $fieldJson = $this->getFieldJson($field, $fieldLabel, false);
          foreach ($fieldJson as $item) {
            array_push($contentJson, $item);
          }
        }
      }
    }

    foreach ($contentJson as $i => $item) {
      if (empty($item)) {
        unset($contentJson[$i]);
      }
    }

    return $contentJson;
  }

  /**
   * @param \Drupal\node\Entity\Node $node
   * @param string $fieldName
   * @return array
   */
  private function getFieldJson($field, $fieldLabel, $isSection) {
    $texts = $this->splitParagraphs($this->fieldValueFormatted($field, FALSE));
    if ($isSection) {
      return $this->getFieldJsonAsSection($fieldLabel, $texts);
    }

    return $this->getFieldJsonAsParagraphs($texts);
  }

  private function getFieldJsonAsSection($title, $content) {
    // handle paragraphs within a section
    foreach ($content as $i => $item) {
      if(!is_array($item)) {
        $content[$i] = $this->getFieldJsonAsParagraphs($item);
      }
    }
    return [
      'type' => 'section',
      'title' => $title,
      'content' => $content,
    ];
  }

  private function getFieldJsonAsParagraphs($text) {
    if (is_array($text)) {
      foreach ($text as $i => $para) {
        $text[$i] = [
          'type' => 'paragraph',
          'text' => trim($para),
        ];
      }
      return $text;
    }
    return [
      'type' => 'paragraph',
      'text' => trim($text),
    ];
  }

}
