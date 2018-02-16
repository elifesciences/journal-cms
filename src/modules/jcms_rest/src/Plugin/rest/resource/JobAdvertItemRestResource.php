<?php

namespace Drupal\jcms_rest\Plugin\rest\resource;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\jcms_rest\Exception\JCMSNotFoundHttpException;
use Drupal\jcms_rest\Response\JCMSRestResponse;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
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
   * @throws JCMSNotFoundHttpException
   */
  public function get(string $id) : JCMSRestResponse {
    $query = \Drupal::entityQuery('node')
      ->condition('changed', \Drupal::time()->getRequestTime(), '<')
      ->condition('type', 'job_advert')
      ->condition('uuid', '%' . $id, 'LIKE');

    if (!$this->viewUnpublished()) {
      $query->condition('status', NodeInterface::PUBLISHED);
    }

    $nids = $query->execute();
    if ($nids) {
      $nid = reset($nids);
      /* @var Node $node */
      $node = Node::load($nid);

      $this->setSortBy(FALSE);
      $response = $this->processDefault($node, $id);

      // Impact statement is optional.
      if ($node->get('field_impact_statement')->count()) {
        $response['impactStatement'] = $this->fieldValueFormatted($node->get('field_impact_statement'));
        if (empty($response['impactStatement'])) {
          unset($response['impactStatement']);
        }
      }

      $response['closingDate'] = $this->formatDate($node->get('field_job_advert_closing_date')->first()->getValue()['value']);
      $response['content'] = $this->deriveContentJson($node);

      $response = new JCMSRestResponse($response, Response::HTTP_OK, ['Content-Type' => $this->getContentType()]);
      $response->addCacheableDependency($node);
      $this->processResponse($response);
      return $response;
    }

    throw new JCMSNotFoundHttpException(t('Job advert with ID @id was not found', ['@id' => $id]));
  }

  /**
   * Derive content json from node.
   */
  private function deriveContentJson(Node $node) : array {
    $contentJson = [];
    $fieldsData = [
      [
        'name' => 'field_job_advert_role_summary',
        'isSection' => FALSE,
      ],
      [
        'name' => 'field_job_advert_experience',
        'isSection' => TRUE,
      ],
      [
        'name' => 'field_job_advert_respons',
        'isSection' => TRUE,
      ],
      [
        'name' => 'field_job_advert_terms',
        'isSection' => TRUE,
      ],
    ];

    foreach ($fieldsData as $fieldData) {
      $field = $node->get($fieldData['name']);
      if (!$field->count()) {
        continue;
      }
      if ($fieldData['isSection']) {
        array_push($contentJson, $this->getFieldJson($field, $this->getFieldLabel($node, $fieldData['name']), TRUE));
      }
      else {
        foreach ($this->getFieldJson($field) as $item) {
          array_push($contentJson, $item);
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
   * Get field label.
   */
  public function getFieldLabel(Node $node, string $fieldName) : string {
    return $node->{$fieldName}->getFieldDefinition()->getLabel();
  }

  /**
   * Get field json.
   */
  public function getFieldJson(FieldItemListInterface $field, string $fieldLabel = '', bool $isSection = FALSE) : array {
    $texts = $this->splitParagraphs($this->fieldValueFormatted($field, FALSE));
    if ($isSection) {
      return self::getFieldJsonAsSection($fieldLabel, $texts);
    }

    return self::getFieldJsonAsParagraphs($texts);
  }

  /**
   * Get field json as section.
   */
  public static function getFieldJsonAsSection(string $title, array $content) : array {
    foreach ($content as $i => $item) {
      if (!is_array($item)) {
        $content[$i] = self::getFieldJsonAsParagraphs($item);
      }
    }
    return [
      'type' => 'section',
      'title' => $title,
      'content' => $content,
    ];
  }

  /**
   * Get field json as paragraphs.
   *
   * @param string|array $text
   *   Can be array or string.
   *
   * @return array
   *   Paragraph blocks.
   */
  public static function getFieldJsonAsParagraphs($text) : array {
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
