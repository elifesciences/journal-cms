<?php

namespace Drupal\jcms_rest\Plugin\rest\resource;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Entity\EntityInterface;
use Drupal\jcms_rest\Exception\JCMSNotFoundHttpException;
use Drupal\jcms_rest\Response\JCMSRestResponse;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "highlight_list_rest_resource",
 *   label = @Translation("Highlight list rest resource"),
 *   uri_paths = {
 *     "canonical" = "/highlights/{list}"
 *   }
 * )
 */
class HighlightListRestResource extends AbstractRestResourceBase {

  /**
   * Responds to GET requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @param string $list
   * @return array|\Symfony\Component\HttpFoundation\JsonResponse
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function get($list) {
    $query = \Drupal::entityQuery('node')
      ->condition('status', \Drupal\node\NodeInterface::PUBLISHED)
      ->condition('changed', \Drupal::time()->getRequestTime(), '<')
      ->condition('type', 'highlight_list')
      ->condition('title', $list);

    $dependencies = [];

    $response_data = [
      'total' => 0,
      'items' => [],
    ];

    $item_nids = [];
    $nids = $query->execute();
    if ($nids) {
      $nid = reset($nids);
      /* @var \Drupal\node\Entity\Node $node */
      $node = \Drupal\node\Entity\Node::load($nid);
      $dependencies[] = $node;
      foreach ($node->get('field_highlight_items')->getValue() as $item) {
        $item_nids[] = $item['target_id'];
      }
    }
    else {
      $query = \Drupal::entityQuery('taxonomy_term')
        ->condition('vid', 'subjects')
        ->condition('field_subject_id.value', $list);

      $tids = $query->execute();
      if ($tids) {
        $query = Database::getConnection()->select('node__field_highlight_item', 'hi');
        $query->addField('hi', 'entity_id', 'item');
        $query->condition('hi.bundle', 'highlight_item');
        $query->leftJoin('node__field_subjects', 's', 's.entity_id = hi.field_highlight_item_target_id');
        $query->leftJoin('taxonomy_term__field_subject_id', 'si', 'si.entity_id = s.field_subjects_target_id');
        $query->addField('si', 'field_subject_id_value', 'subject_id');
        $query->leftJoin('node__field_episode_chapter', 'ec', 'ec.field_episode_chapter_target_id = hi.field_highlight_item_target_id');
        $query->leftJoin('node__field_subjects', 'sp', 'sp.entity_id = ec.entity_id');
        $query->leftJoin('taxonomy_term__field_subject_id', 'spi', 'spi.entity_id = sp.field_subjects_target_id');
        $query->addField('spi', 'field_subject_id_value', 'podcast_subject_id');
        $query->innerJoin('node_field_data', 'nfd', 'nfd.nid = hi.field_highlight_item_target_id');
        // Use the created date for all content other than collections which should use the changed date. Created date doe articles is set to status date.
        $query->addExpression("IF(nfd.type='collection', nfd.changed, nfd.created)", 'order_date');
        $query->orderBy('order_date', 'DESC');
        $db_or = new Condition('OR');
        $db_or->condition('si.field_subject_id_value', $list);
        $db_or->condition('spi.field_subject_id_value', $list);
        $query->condition($db_or);

        if ($results = $query->execute()->fetchAllKeyed()) {
          $item_nids = array_keys($results);
        }
      }
      else {
        throw new JCMSNotFoundHttpException(t('Highlights with ID @id was not found', ['@id' => $list]));
      }
    }

    if (!empty($item_nids)) {
      $response_data['total'] = count($item_nids);
      /* @var \Drupal\node\Entity\Node[] $items */
      if ($items = \Drupal\node\Entity\Node::loadMultiple($this->filterPageAndOrderArray($item_nids))) {
        foreach ($items as $item) {
          $dependencies[] = $item;
          if ($highlight = $this->getItem($item)) {
            $response_data['items'][] = $this->getItem($item);
          }
        }
      }
    }

    $response = new JCMSRestResponse($response_data, Response::HTTP_OK, ['Content-Type' => $this->getContentType()]);
    foreach ($dependencies as $dependency) {
      $response->addCacheableDependency($dependency);
    }
    return $response;
  }

  /**
   * Takes a highlight item node and builds a snippet from it.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node
   *
   * @return array|bool
   */
  public function getItem(EntityInterface $node) {
    /* @var Node $node */
    $item = $this->getEntityQueueItem($node, $node->get('field_highlight_item'), FALSE);

    if ($item) {
      // authorLine is optional.
      if ($node->get('field_author_line')->count()) {
        $item['authorLine'] = $node->get('field_author_line')->getString();
      }

      // Image is optional.
      if ($image = $this->processFieldImage($node->get('field_image'), FALSE, 'thumbnail', TRUE)) {
        $item['image'] = $image;
      }

      return $item;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Apply filter for page, per-page and order.
   *
   * @param array $nids
   * @param mixed
   *
   * @return array
   */
  protected function filterPageAndOrderArray($nids, $sort_by = NULL) {
    $request_options = $this->getRequestOptions();

    if ($request_options['order'] == 'asc') {
      $nids = array_reverse($nids);
    }

    $nids = array_slice($nids, ($request_options['page'] - 1) * $request_options['per-page'], $request_options['per-page']);

    return $nids;
  }

}
