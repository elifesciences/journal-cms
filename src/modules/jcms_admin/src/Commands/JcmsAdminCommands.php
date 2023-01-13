<?php

namespace Drupal\jcms_admin\Commands;

use Drupal\entityqueue\Entity\EntitySubqueue;
use Drupal\node\Entity\Node;
use Drush\Commands\DrushCommands;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * JcmsAdmin Drush commandfile.
 */
class JcmsAdminCommands extends DrushCommands {

  /**
   * Transfer HTML from content to content preview or vice versa.
   *
   * @param string $id
   *   Node id of content to perform transfer.
   * @param array $options
   *   Array of options whose values come from cli, aliases, config, etc.
   *
   * @option to-live
   *   Transfer from preview to live (default: true).
   * @usage drush jcms-transfer-content 123
   *   Transfer HTML from content to content preview for node with ID 123.
   * @validate-module-enabled jcms_admin
   *
   * @command jcms:transfer-content
   * @aliases jcms-transfer-content
   */
  public function transferContent(string $id, array $options = ['to-live' => TRUE]) {
    $to_live = ((int) $options['to-live']) === 0 ? FALSE : TRUE;
    $node = Node::load($id);
    $node = \Drupal::service('jcms_admin.transfer_content')->transfer($node, $to_live);
    $node->save();
    $this->output()->writeln(dt('Transfer complete!'));
  }

  /**
   * Update a profile with the orcid ID.
   *
   * @param string $id
   *   ID of profile.
   * @param string $orcid
   *   ORCID ID.
   *
   * @usage drush jcms-profile-orcid 3ec1c7f1 0000-0001-8615-6409
   *   Set the ORCID ID to 0000-0001-8615-6409 for the profile with ID 3ec1c7f1
   * @validate-module-enabled jcms_admin
   *
   * @command jcms:profile-orcid
   * @aliases jcms-profile-orcid
   */
  public function profileOrcid(string $id, string $orcid) {
    if (!preg_match('/^0000\-000(1\-[5-9]|2\-[0-9]|3\-[0-4])\d{3}\-\d{3}[\dX]$/', $orcid)) {
      $this->output()->writeln(dt('Invalid ORCID ID detected: :orcid', [':orcid' => $orcid]));
    }
    else {
      $query = \Drupal::entityQuery('node')
        ->accessCheck(TRUE)
        ->condition('type', 'person')
        ->condition('uuid', '%' . $id, 'LIKE');

      $nids = $query->execute();
      if ($nids) {
        $nid = reset($nids);
        $node = Node::load($nid);
        $node->set('field_person_orcid', $orcid);
        $node->save();
        $this->output()->writeln(dt('Profile updated! (:id, ":title", :orcid)', [
          ':id' => $id,
          ':title' => $node->getTitle(),
          ':orcid' => $orcid,
        ]));
      }
      else {
        $this->output()->writeln(dt('No profile found: (:id)', [':id' => $id]));
      }
    }
  }

  /**
   * Trigger re-save of profiles.
   *
   * @param array $options
   *   Array of options whose values come from cli, aliases, config, etc.
   *
   * @option focus
   *   filter by research focus
   * @option organism
   *   filter by research organism
   * @usage drush jcms-profile-notify
   *   Re-save all profiles to trigger notifications
   * @usage drush jcms-profile-notify --focus="<i>Sulfolobus</i>"
   *   Re-save profiles with focus "<i>Sulfolobus</i>" to trigger notifications
   * @usage drush jcms-profile-notify --organism="amphibians"
   *   Re-save profiles with organism "amphibians" to trigger notifications
   * @validate-module-enabled jcms_admin
   *
   * @command jcms:profile-notify
   * @aliases jcms-profile-notify
   */
  public function profileNotify(array $options = [
    'focus' => NULL,
    'organism' => NULL,
  ]) {
    $verbose = $this->output()->getVerbosity() > OutputInterface::VERBOSITY_NORMAL;
    $focus = $options['focus'];
    $organism = $options['organism'];

    foreach (['focus', 'organism'] as $i) {
      if (!is_string(${$i}) || empty(${$i})) {
        ${$i} = NULL;
      }
    }

    $profiles = [];

    $load_profiles = function ($type, $value) {
      $tid = \Drupal::entityQuery('taxonomy_term')
        ->accessCheck(TRUE)
        ->condition('vid', $type)
        ->condition('name', $value)
        ->execute();

      return !empty($tid) ? _jcms_admin_profiles_from_focuses_organisms($type, current($tid)) : [];
    };

    if ($focus) {
      $profiles = $load_profiles('research_focuses', $focus);
    }
    elseif ($organism) {
      $profiles = $load_profiles('research_organisms', $organism);
    }
    else {
      $people = \Drupal::entityQuery('node')
        ->accessCheck(TRUE)
        ->condition('type', 'person')
        ->execute();
      if ($people) {
        $profiles = array_values($people);
      }
    }

    $nodes = Node::loadMultiple($profiles);
    if (!empty($nodes)) {
      $this->output()->writeln(dt('Processing !count profile(s)', [
        '!count' => count($nodes),
      ]));
      foreach ($nodes as $node) {
        if ($verbose) {
          // This is useful if content needs to be manually reassigned.
          $this->output()->writeln(dt(':type: :title (/node/!nid)', [
            ':type' => $node->bundle(),
            ':title' => $node->getTitle(),
            '!nid' => $node->id(),
          ]));
        }
        $node->setChangedTime(\Drupal::time()->getRequestTime());
        $node->save();
      }
    }
  }

  /**
   * Populate covers entityqueue with random covers.
   *
   * @validate-module-enabled jcms_admin
   *
   * @command jcms:covers-random
   * @aliases jcms-covers-random
   */
  public function coversRandom() {
    $covers = \Drupal::entityQuery('node')
      ->accessCheck(TRUE)
      ->condition('type', 'cover')
      ->exists('field_image')
      ->execute();
    shuffle($covers);
    $covers = array_slice($covers, 0, 3);
    array_walk($covers, function (&$cover) {
      $node = Node::load($cover);
      $node->set('moderation_state', 'published');
      $node->save();
      $cover = [
        'target_id' => $cover,
      ];
    });

    $subqueue = EntitySubqueue::load('covers');
    $subqueue->set('items', $covers);
    $subqueue->save();

    $this->output()->writeln(dt('Covers list populated with random covers'));
  }

}
