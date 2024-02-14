<?php

namespace Drupal\jcms_migrate\Commands;

use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityInterface;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\taxonomy\Entity\Term;
use Drush\Commands\DrushCommands;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A Drush commandfile.
 */
class JcmsMigrateCommands extends DrushCommands {

  /**
   * Switch all content from one major subject area to another.
   *
   * @param string $from
   *   MSA to switch from.
   * @param string|null $to
   *   MSA to switch from.
   *
   * @usage drush msa-switch biochemistry biochemistry-chemical-biology
   *   Switch content from biochemistry to biochemistry-chemical-biology.
   * @validate-module-enabled jcms_migrate
   *
   * @command msa:switch
   * @aliases msa-s,msa-switch
   */
  public function msaSwitch(string $from, string $to = NULL) {
    // If $to is NULL then there is no need to trigger the save.
    $skip_save = (is_null($to)) ? TRUE : FALSE;
    // Allow $to to be null to force save of all content under this MSA.
    $to = $to ?? $from;

    $verbose = $this->output()->getVerbosity() > OutputInterface::VERBOSITY_NORMAL;
    // Verify that $from and $to are recognised.
    if (is_numeric($from)) {
      $msa_from = [$from];
    }
    else {
      $msa_from = \Drupal::entityQuery('taxonomy_term')
        ->accessCheck(FALSE)
        ->condition('vid', 'subjects')
        ->condition('field_subject_id.value', $from)
        ->execute();
    }

    if (!$msa_from) {
      throw new \Exception(dt('!from is not a recognised major subject area.', ['!from' => $from]));
    }
    $msa_to = \Drupal::entityQuery('taxonomy_term')
      ->accessCheck(FALSE)
      ->condition('vid', 'subjects')
      ->condition('field_subject_id.value', $to)
      ->execute();
    if (!$msa_to) {
      throw new \Exception(dt('!to is not a recognised major subject area.', ['!to' => $to]));
    }
    $this->output()->writeln(dt('Switch content from !from to !to.', [
      '!from' => $from,
      '!to' => $to,
    ]));

    // Retrieve target_id values for $from and $to.
    $msa_from_target_id = current($msa_from);
    $msa_to_target_id = current($msa_to);
    $content = [];

    // Gather all blog_article, collection and podcast_episode for $from.
    foreach (['blog_article', 'collection', 'podcast_episode'] as $type) {
      $result = \Drupal::entityQuery('node')
        ->accessCheck(FALSE)
        ->condition('field_subjects.target_id', $msa_from_target_id, 'IN')
        ->condition('type', $type)
        ->execute();
      if ($result) {
        $content[$type] = array_values($result);
      }
    }

    // Gather all of type person for $from.
    $research_details = Database::getConnection()->select('node__field_research_details', 'rd');
    $research_details->addField('rd', 'entity_id', 'person_nid');
    $research_details->addField('rd', 'field_research_details_target_id', 'research_details_id');
    $research_details->innerJoin('paragraph__field_research_expertises', 're', 're.entity_id = rd.field_research_details_target_id');
    $research_details->condition('re.field_research_expertises_target_id', $msa_from_target_id);
    $research_details->condition('rd.bundle', 'person');
    $people = $research_details->execute()->fetchAllAssoc('person_nid');
    if ($people) {
      $content['person'] = array_keys($people);
    }

    $msa_from_id = function (int $tid) {
      static $msa = [];

      if (empty($msa[$tid])) {
        $term = Term::load($tid);
        $msa[$tid] = $term->get('field_subject_id')->getString();
      }

      return $msa[$tid];
    };

    $msa_from_ids = function (array $tids = []) use ($msa_from_id) {
      $msa = [];

      foreach ($tids as $tid) {
        $msa[$tid] = $msa_from_id($tid);
      }

      return $msa;
    };

    // Function to set new MSA values if $from and $to are different.
    $swap_msa_values = function (EntityInterface $entity, $field_name, &$old = [], &$new = []) use ($msa_from_target_id, $msa_to_target_id, $msa_from_ids) {
      if ($msa_from_target_id !== $msa_to_target_id || $verbose) {
        $values = $entity->get($field_name)->getValue();
        $new_values = [];
        foreach ($values as $k => $value) {
          $target_id = $value['target_id'];
          $old[$target_id] = TRUE;
          if ($target_id === $msa_from_target_id) {
            $target_id = $msa_to_target_id;
          }
          // Remove duplicates.
          if (!isset($new_values[$target_id])) {
            $new[$target_id] = TRUE;
            $new_values[$target_id] = [
              'target_id' => $target_id,
            ];
          }
        }
        $old = $msa_from_ids(array_keys($old));
        $new = $msa_from_ids(array_keys($new));
        if ($msa_from_target_id !== $msa_to_target_id) {
          $entity->set($field_name, array_values($new_values));
        }
      }
    };

    foreach ($content as $type => $nids) {
      $nodes = Node::loadMultiple($nids);
      $this->output()->writeln(dt('Processing !count of type "!type"', [
        '!count' => count($nodes),
        '!type' => $type,
      ]));
      /** @var \Drupal\node\NodeInterface $node */
      foreach ($nodes as $node) {
        $old = [];
        $new = [];
        if ($type === 'person') {
          $details = Paragraph::load($people[$node->id()]->research_details_id);
          $swap_msa_values($details, 'field_research_expertises', $old, $new);
          $details->save();
        }
        else {
          $swap_msa_values($node, 'field_subjects', $old, $new);
        }
        if ($verbose) {
          // This is useful if content needs to be manually reassigned.
          $this->output()->writeln(dt(':type (:uuid): :title from (":old" to ":new") (/node/!nid)', [
            ':type' => $node->bundle(),
            ':uuid' => substr($node->uuid(), -8),
            ':title' => $node->getTitle(),
            ':old' => implode(', ', $old),
            ':new' => implode(', ', $new),
            '!nid' => $node->id(),
          ]));
        }
        if (!$skip_save) {
          $node->save();
        }
      }
    }

    if (!$skip_save) {
      $this->output()->writeln(dt('Switching complete!'));
    }
  }

  /**
   * Purges unused paragraph revisions for the given field.
   *
   * @param string $field
   *   Field name.
   * @param array $options
   *   Array of options whose values come from cli, aliases, config, etc.
   *
   * @option limit
   *   Limit on the number of deletions to do.
   * @option feedback
   *   Receive feedback after a specified number of deletions. (default: 100)
   * @usage drush paragraphs-revisions-purge field_article_json
   *   Purge all unused paragraph revisions for the field field_article_json.
   * @usage drush paragraphs-revisions-purge
   *   Purge all unused paragraph revisions on any field.
   * @usage drush paragraphs-revisions-purge --limit=1000
   *   Purge 1000 unused paragraph revisions on any field.
   * @usage drush paragraphs-revisions-purge --feedback=100
   *   Provide feedback for every 100 unused paragraph revisions purged.
   * @validate-module-enabled jcms_migrate
   *
   * @command paragraphs:revisions-purge
   * @aliases pr-purge,paragraphs-revisions-purge
   */
  public function paragraphsRevisionsPurge(string $field = NULL, array $options = [
    'limit' => NULL,
    'feedback' => NULL,
  ]) {
    $logger = \Drupal::logger('jcms_revisions_purge');
    $limit = $options['limit'] ? (int) $options['limit'] : 0;
    $feedback_frequency = $options['feedback'] ? (int) $options['feedback'] : 100;

    // Count number of paragraph revisions that are not a default revision.
    $query = Database::getConnection()->select('paragraphs_item_revision', 'pir');
    if (!empty($field) && Database::getConnection()->schema()->tableExists('node__' . $field)) {
      $query->join('node__' . $field, 'nf', 'nf.' . $field . '_target_id=pir.id');
    }
    $query->leftjoin('paragraphs_item', 'pi', 'pi.revision_id=pir.revision_id');
    $query->isNull('pi.id');
    $count = $query->countQuery()->execute()->fetch()->expression;
    $this->output()->writeln(dt('Number of revisions to be deleted is !count.', ['!count' => $count]));
    $logger->info('Number of revisions to be deleted', ['count' => $count]);

    if ($count > 0 && $limit >= 0) {
      $count_deleted = 0;
      do {
        $query = Database::getConnection()->select('paragraphs_item_revision', 'pir');
        if (!empty($field)) {
          $query->join('node__' . $field, 'nf', 'nf.' . $field . '_target_id=pir.id');
        }
        $query->leftjoin('paragraphs_item', 'pi', 'pi.revision_id=pir.revision_id');
        $query->isNull('pi.id');
        $query->addField('pir', 'revision_id');
        if ($limit > $feedback_frequency) {
          $query->range(0, $feedback_frequency);
          $limit -= $feedback_frequency;
        }
        elseif ($limit > 0) {
          $query->range(0, $limit);
          $limit = -1;
        }
        else {
          $query->range(0, $feedback_frequency);
        }
        $revision_ids = $query->execute()->fetchCol();
        if ($revision_ids) {
          foreach ($revision_ids as $revision_id) {
            try {
              \Drupal::entityTypeManager()->getStorage('paragraph')->deleteRevision($revision_id);
              $count_deleted++;
              if (($count_deleted % $feedback_frequency) == 0) {
                $this->output()->writeln(dt('Number of revisions deleted is !count.', ['!count' => $count_deleted]));
                $logger->info('Progress of revisions deleted.', ['count' => $count_deleted]);
              }
            }
            catch (\Exception $e) {
              // This should not be reached but is a precaution against attempts
              // to delete default revision which would thrown an exception.
            }
          }
        }
        else {
          // No more revisions to delete.
          $limit = -1;
          break;
        }
      } while ($limit >= 0);
      $this->output()->writeln(dt('Total number of revisions deleted is !count.', ['!count' => $count_deleted]));
      $logger->info('Completed deletion of revisions', ['count' => $count_deleted]);
    }

    // Get the number of revisions left to delete.
    $query = Database::getConnection()->select('paragraphs_item_revision', 'pir');
    if (!empty($field) && Database::getConnection()->schema()->tableExists('node__' . $field)) {
      $query->join('node__' . $field, 'nf', 'nf.' . $field . '_target_id=pir.id');
    }
    $query->leftjoin('paragraphs_item', 'pi', 'pi.revision_id=pir.revision_id');
    $query->isNull('pi.id');
    $count = $query->countQuery()->execute()->fetch()->expression;
    if ($count > 0) {
      $this->output()->writeln(dt('Completed with !count revisions left in place.', ['!count' => $count]));
      $logger->info('Completed with some revisions left in place.', ['remaining' => $count]);
    }
    else {
      $field = !empty($field) ? $field : 'all fields';
      $this->output()->writeln(dt('Success! All revisions for !field have been purged.', ['!field' => $field]));
      $logger->info('Success! All revisions have been purged.', ['field' => $field]);
    }
  }

  /**
   * Optimise paragraph revision tables. Don't run until after purge.
   *
   * @usage drush paragraphs-revisions-optimise
   *   Optimise all paragraph revision tables.
   * @validate-module-enabled jcms_migrate
   *
   * @command paragraphs:revisions-optimise
   * @aliases pr-optimise,paragraphs-revisions-optimise
   */
  public function paragraphsRevisionsOptimise() {
    $logger = \Drupal::logger('jcms_revisions_optimise');
    $this->output()->writeln(dt('Optimise paragraph revision tables.'));
    $logger->info('Optimise paragraph revision tables.');
    $table_list = Database::getConnection()->query("SHOW TABLES LIKE 'paragraph_r%'")->fetchCol();
    $table_list[] = 'paragraphs_item_revision';
    $table_list[] = 'paragraphs_item_revision_field_data';
    Database::getConnection()->query('OPTIMIZE TABLE ' . implode(',', $table_list));
    $this->output()->writeln(dt('All paragraph revision tables have been optimised.'));
    $logger->info('All paragraph revision tables have been optimised.');
  }

  /**
   * Create a person.
   *
   * @param string $type
   *   Must be an existing type (e.g. reviewing-editor, senior-editor etc).
   * @param string $surname
   *   Surname of person.
   * @param array $options
   *   Array of options whose values come from cli, aliases, config, etc.
   *
   * @option given
   *   Given names of person.
   * @option email
   *   Email address of person.
   * @option match
   *   Override current person if match found.
   * @option upsert
   *   Create new or update existing.
   * @usage drush create-person reviewing-editor Jones
   *   Create a person of type reviewing-editor with surname Jones.
   * @validate-module-enabled jcms_migrate
   *
   * @command create:person
   * @aliases create-person
   */
  public function createPerson(string $type, string $surname, array $options = [
    'given' => NULL,
    'email' => NULL,
    'match' => NULL,
    'upsert' => NULL,
  ]) {
    $given = !is_bool($options['given']) ? trim($options['given']) : NULL;
    $email = !is_bool($options['email']) ? trim($options['email']) : NULL;
    $match = $options['match'];
    $upsert = (!$match && $options['upsert']);
    $person = NULL;

    if ($match || $upsert) {
      $query = \Drupal::entityQuery('node')
        ->accessCheck(FALSE)
        ->condition('field_person_name_surname.value', $surname)
        ->condition('field_person_type.value', $type);

      if ($given) {
        $query
          ->condition('field_person_name_given.value', $given . '%', 'LIKE');
      }

      if ($result = $query->execute()) {
        $person = Node::load(array_shift($result));
      }
    }

    if ((!$match && !$upsert) || ($upsert && is_null($person))) {
      /** @var \Drupal\node\Entity\NodeInterface $person */
      $person = Node::create([
        'type' => 'person',
        'title' => $surname,
      ]);

      $person->field_person_type = [
        [
          'value' => $type,
        ],
      ];

      $person->field_person_name_surname = [
        [
          'value' => $surname,
        ],
      ];

      if ($given) {
        $person->field_person_name_given = [
          [
            'value' => $given,
          ],
        ];
      }

      $person->setOwnerId(1);
      $person->setPublished();
    }

    if ($person instanceof NodeInterface) {
      if ($email) {
        $person->field_person_email = [
          [
            'value' => $email,
          ],
        ];
      }

      $person->save();
      $this->output()->writeln(dt($person->label() . ' (' . $type . ') - ' . ($person->isNew() ? 'created' : 'updated') . '.'));
    }
    elseif ($match) {
      $this->output()->writeln(dt(implode(' ', array_filter([$given, $surname])) . ' (' . $type . ') - not found.'));
    }
  }

  /**
   * Resave all nodes of a certain type.
   *
   * @param string $type
   *   Content type.
   * @param array $options
   *   Array of options whose values come from cli, aliases, config, etc.
   *
   * @option limit
   *   Limit on the number of resaves to do.
   * @option feedback
   *   Receive feedback after a specified number of resaves. (default: 100)
   * @usage drush resave-nodes blog_article
   *   Create a person of type reviewing-editor with surname Jones.
   * @usage drush resave-nodes person --limit=10
   *   Resave 10 nodes of content type person.
   * @usage drush resave-nodes blog_article --feedback=100
   *   Provide feedback for every 50 blog_articles resaved.
   * @validate-module-enabled jcms_migrate
   *
   * @command resave:nodes
   * @aliases resave-nodes
   */
  public function resaveNodes(string $type, array $options = [
    'limit' => NULL,
    'feedback' => NULL,
  ]) {
    $limit = $options['limit'] ? (int) $options['limit'] : 0;
    $feedback_frequency = $options['feedback'] ? (int) $options['feedback'] : 100;

    $query = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->condition('type', $type);

    if ($limit > 0) {
      $query->range(0, $limit);
    }

    $nids = $query->execute();

    if ($nids) {
      $count = count($nids);
      $this->output()->writeln(dt('Number of !type nodes to be resaved is !count.', [
        '!type' => $type,
        '!count' => count($nids),
      ]));
      if ($feedback_frequency > $count) {
        $feedback_frequency = $count;
      }

      for ($i = 0; $i < $count; $i += $feedback_frequency) {
        $nodes = Node::loadMultiple(array_slice($nids, $i, $feedback_frequency));
        foreach ($nodes as $node) {
          $node->save();
        }
        $this->output()->writeln(dt('Number of !type nodes resaved is !count.', [
          '!type' => $type,
          '!count' => $i + count($nodes),
        ]));
      }
    }
  }

  /**
   * Amend publish date of content.
   *
   * @param string $type
   *   Content type.
   * @param string $id
   *   ID.
   * @param string $date
   *   Date.
   *
   * @usage drush amend-publish-date interview "09d713c1" "2019-09-26"
   *   Set the date for interview with ID "09d713c1" to "2019-09-26".
   * @validate-module-enabled jcms_migrate
   *
   * @command amend:publish-date
   * @aliases apd,amend-publish-date
   */
  public function amendPublishDate(string $type, string $id, string $date) {
    $content = [
      $id => $date,
    ];

    $query = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->condition('uuid', '(' . implode('|', array_keys($content)) . ')', 'REGEXP')
      ->condition('type', $type);

    $nids = $query->execute();
    if ($nids) {
      $nodes = Node::loadMultiple($nids);
      foreach ($nodes as $node) {
        $id = substr($node->uuid(), -8);
        $current_date = $node->getCreatedTime();
        $new_date = strtotime($content[$id] . ' ' . date('H:i:s', $current_date));
        $node->set('created', $new_date);
        $node->save();
        $this->output()->writeln(dt('!type (!uuid): "!title" publish date changed from "!current-date" to "!new-date" (/node/!nid)', [
          '!type' => $node->bundle(),
          '!uuid' => substr($node->uuid(), -8),
          '!title' => trim($node->getTitle()),
          '!current-date' => date('Y-m-d', $current_date),
          '!new-date' => date('Y-m-d', $new_date),
          '!nid' => $node->id(),
        ]));
      }
    }
  }

}
