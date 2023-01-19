<?php

namespace Drupal\jcms_digest\Hooks;

use Drupal\Core\Entity\EntityInterface;
use Drupal\jcms_digest\Entity\Digest;
use Drupal\jcms_digest\FetchDigest;

/**
 * Node Presave service.
 *
 * @package Drupal\jcms_digest
 */
final class NodePresave {

  /**
   * Store constructor argument FetchDigest.
   *
   * @var \Drupal\jcms_digest\FetchDigest
   */
  private $fetchDigest;

  /**
   * NodePresave constructor.
   */
  public function __construct(FetchDigest $fetch_digest) {
    $this->fetchDigest = $fetch_digest;
  }

  /**
   * Gets the digest data.
   */
  public function getDigestById(string $id) : Digest {
    return $this->fetchDigest->getDigestById($id);
  }

  /**
   * Sets the published date on the node.
   */
  public function setPublishedDate(EntityInterface $entity, Digest $digest) {
    $entity->set('created', strtotime($digest->getJsonObject()->published ?? $digest->getJsonObject()->updated));
  }

  /**
   * Sets the updated date on the node.
   */
  public function setUpdatedDate(EntityInterface $entity, Digest $digest) {
    $entity->set('changed', strtotime($digest->getJsonObject()->updated));
  }

  /**
   * Sets the published status of the node.
   */
  public function setPublishedStatus(EntityInterface $entity, Digest $digest) {
    $entity->set('status', $digest->getJsonObject()->stage === 'published');
  }

  /**
   * Sets the digest subjects.
   */
  public function setSubjectTerms(EntityInterface $entity, Digest $digest) {
    $json = $digest->getJsonObject();
    if (is_object($json) && property_exists($json, 'subjects')) {
      // Unset the terms first.
      $entity->set('field_subjects', []);
      foreach ($json->subjects as $subject) {
        if (isset($subject->id)) {
          $tid = $this->loadTermIdByIdField($subject->id);
          if ($tid) {
            $entity->get('field_subjects')->appendItem(['target_id' => $tid]);
          }
        }
      }
    }
  }

  /**
   * Returns a taxonomy term ID, loading the term by its string ID field.
   */
  private function loadTermIdByIdField(string $id): int {
    $tid = 0;
    $query = \Drupal::entityQuery('taxonomy_term')
      ->condition('field_subject_id', $id);
    $tids = $query->execute();
    if ($tids) {
      $tid = reset($tids);
    }
    return $tid;
  }

}
