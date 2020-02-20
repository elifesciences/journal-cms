<?php

namespace Drupal\jcms_rest\Plugin\rest\resource;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jcms_rest\Response\JCMSRestResponse;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "press_package_list_rest_resource",
 *   label = @Translation("Press package list rest resource"),
 *   uri_paths = {
 *     "canonical" = "/press-packages"
 *   }
 * )
 */
class PressPackageListRestResource extends AbstractRestResourceBase {

  /**
   * Responds to GET requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @todo - elife - nlisgo - Handle version specific requests
   */
  public function get() : JCMSRestResponse {
    $base_query = \Drupal::entityQuery('node')
      ->condition('changed', \Drupal::time()->getRequestTime(), '<')
      ->condition('type', 'press_package');

    if (!$this->viewUnpublished()) {
      $base_query->condition('status', NodeInterface::PUBLISHED);
    }

    $this->filterSubjects($base_query);

    $count_query = clone $base_query;
    $items_query = clone $base_query;
    $response_data = [
      'total' => 0,
      'items' => [],
    ];
    $nodes = [];
    if ($total = $count_query->count()->execute()) {
      $response_data['total'] = (int) $total;
      $this->filterPageAndOrder($items_query);
      $nids = $items_query->execute();
      $nodes = Node::loadMultiple($nids);
      if (!empty($nodes)) {
        foreach ($nodes as $node) {
          if ($item = $this->getItem($node)) {
            $response_data['items'][] = $item;
          }
        }
      }
    }
    $response = new JCMSRestResponse($response_data, Response::HTTP_OK, ['Content-Type' => $this->getContentType()]);
    $response->addCacheableDependencies($nodes);
    $this->processResponse($response);
    return $response;
  }

  /**
   * Takes a node and builds an item from it.
   *
   * @return array|bool
   *   Return item, if found.
   */
  public function getItem(EntityInterface $node) {
    /* @var Node $node */
    $item = $this->processDefault($node);

    // Impact statement is optional.
    if ($node->get('field_impact_statement')->count()) {
      $item['impactStatement'] = $this->fieldValueFormatted($node->get('field_impact_statement'));
      if (empty($item['impactStatement'])) {
        unset($item['impactStatement']);
      }
    }

    // Subjects are optional.
    if ($node->get('field_related_content')->count()) {
      $articles = [];
      foreach ($node->get('field_related_content')->referencedEntities() as $related) {
        if ($article = $this->getArticleSnippet($related)) {
          $articles[] = $article;
        }
      }
      if (!empty($articles)) {
        $subjects = $this->subjectsFromArticles($articles);
        if (!empty($subjects)) {
          $item['subjects'] = $subjects;
        }
      }
    }

    return $item;
  }

  /**
   * Apply filter for subjects by amending query.
   */
  protected function filterSubjects(QueryInterface &$query) {
    $subjects = $this->getRequestOption('subject');
    if (!empty($subjects)) {
      $query->condition('field_related_content.entity.field_subjects.entity.field_subject_id.value', $subjects, 'IN');
    }
  }

}
