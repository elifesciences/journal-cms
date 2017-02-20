<?php

namespace Drupal\jcms_rest\Plugin\rest\resource;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Entity\EntityInterface;
use Drupal\jcms_rest\Exception\JCMSNotFoundHttpException;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\JsonResponse;
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
      ->condition('status', NODE_PUBLISHED)
      ->condition('changed', REQUEST_TIME, '<')
      ->condition('type', 'highlight_list')
      ->condition('title', $list);
    $response = [];

    $nids = $query->execute();
    if ($nids) {
      $nid = reset($nids);
      /* @var \Drupal\node\Entity\Node $node */
      $node = \Drupal\node\Entity\Node::load($nid);

      if ($node->get('field_highlight_items')->count()) {
        foreach ($node->get('field_highlight_items')->referencedEntities() as $item) {
          $response[] = $this->getHighlightItem($item);
        }
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
        $query->innerJoin('node__field_highlight_items', 'his', 'his.field_highlight_items_target_id = hi.entity_id');
        $query->addField('his', 'delta');
        $query->orderBy('his.delta', 'ASC');
        $query->leftJoin('node__field_subjects', 's', 's.entity_id = hi.field_highlight_item_target_id');
        $query->leftJoin('taxonomy_term__field_subject_id', 'si', 'si.entity_id = s.field_subjects_target_id');
        $query->addField('si', 'field_subject_id_value', 'subject_id');
        $query->leftJoin('node__field_episode_chapter', 'ec', 'ec.field_episode_chapter_target_id = hi.field_highlight_item_target_id');
        $query->leftJoin('node__field_subjects', 'sp', 'sp.entity_id = ec.entity_id');
        $query->leftJoin('taxonomy_term__field_subject_id', 'spi', 'spi.entity_id = sp.field_subjects_target_id');
        $query->addField('spi', 'field_subject_id_value', 'podcast_subject_id');
        $query->innerJoin('node_field_data', 'nfd', 'nfd.nid = hi.field_highlight_item_target_id');
        $query->orderBy('nfd.created', 'DESC');
        $db_or = new Condition('OR');
        $db_or->condition('si.field_subject_id_value', $list);
        $db_or->condition('spi.field_subject_id_value', $list);
        $query->condition($db_or);

        if ($results = $query->execute()->fetchAllKeyed()) {
          /* @var \Drupal\node\Entity\Node[] $items */
          if ($items = \Drupal\node\Entity\Node::loadMultiple(array_keys($results))) {
            $response = [];
            foreach ($items as $item) {
              $response[] = $this->getHighlightItem($item);
            }
          }
        }
      }
      else {
        throw new JCMSNotFoundHttpException(t('Highlights with ID @id was not found', ['@id' => $list]));
      }
    }

    $response = new JsonResponse($response, Response::HTTP_OK, ['Content-Type' => 'application/vnd.elife.highlights+json;version=1']);
    return $response;
  }

  /**
   * Takes a highlight item node and builds a snippet from it.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node
   *
   * @return array
   */
  public function getHighlightItem(EntityInterface $node) {
    /* @var Node $node */
    $item = $this->getEntityQueueItem($node, $node->get('field_highlight_item'), FALSE);

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

}
