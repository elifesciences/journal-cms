<?php

namespace Drupal\jcms_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Process the research detail values into paragraphs.
 *
 * @MigrateProcessPlugin(
 *   id = "jcms_research_details"
 * )
 */
class JCMSResearchDetails extends AbstractJCMSContainerFactoryPlugin {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    list($expertises, $focuses, $organisms, $profile) = $value;
    $expertises = !empty($expertises) ? $this->subjectSourceIDs(explode('|', $expertises)) : [];
    $focuses = !empty($focuses) ? $this->cleanupFocusesAndOrganisms(explode('|', $focuses)) : [];
    $organisms = !empty($organisms) ? $this->cleanupFocusesAndOrganisms(explode('|', $organisms)) : [];
    $profile_keywords = !empty($profile) ? $this->gatherFocusesFromProfile($profile) : [];

    if (!empty($profile_keywords)) {
      $focuses = array_unique(array_merge($focuses, $profile_keywords));
    }

    // Convert source_ids to destination_ids.
    $dest_expertises = $this->migrationDestionationIDs('jcms_subjects_json', $expertises, $migrate_executable, $row, $destination_property);
    $dest_focuses = $this->migrationDestionationIDs('jcms_research_focuses_json', $focuses, $migrate_executable, $row, $destination_property);
    $dest_organisms = $this->migrationDestionationIDs('jcms_research_organisms_json', $organisms, $migrate_executable, $row, $destination_property);

    $values = [];
    if ($dest_expertises) {
      $values['field_research_expertises'] = [];
      foreach ($dest_expertises as $dest_expertise) {
        $values['field_research_expertises'][] = [
          'target_id' => $dest_expertise,
        ];
      }
    }
    if ($dest_focuses) {
      $values['field_research_focuses'] = [];
      foreach ($dest_focuses as $dest_focus) {
        $values['field_research_focuses'][] = [
          'target_id' => $dest_focus,
        ];
      }
    }
    if ($dest_organisms) {
      $values['field_research_organisms'] = [];
      foreach ($dest_organisms as $dest_organism) {
        $values['field_research_organisms'][] = [
          'target_id' => $dest_organism,
        ];
      }
    }

    if (!empty($values)) {
      $values['type'] = 'research_details';
      $paragraph = Paragraph::create($values);
      $paragraph->save();
      return [
        'target_id' => $paragraph->id(),
        'target_revision_id' => $paragraph->getRevisionId(),
      ];
    }

    return NULL;
  }

  /**
   * Return an array of source ids for the subjects of expertise.
   *
   * @param array $subjects
   * @return array
   */
  public function subjectSourceIDs($subjects) {
    foreach ($subjects as $k => $subject) {
      switch ($subject) {
        case 'Plant biology':
          $subjects[$k] = 'plant-biology';
          break;
        case 'Neuroscience':
          $subjects[$k] = 'neuroscience';
          break;
        case 'Microbiology & infectious disease':
          $subjects[$k] = 'microbiology-infectious-disease';
          break;
        case 'Immunology':
          $subjects[$k] = 'immunology';
          break;
        case 'Human biology & medicine':
          $subjects[$k] = 'human-biology-medicine';
          break;
        case 'Genomics & evolutionary biology':
          $subjects[$k] = 'genomics-evolutionary-biology';
          break;
        case 'Genes & chromosomes':
          $subjects[$k] = 'genes-chromosomes';
          break;
        case 'Epidemiology & global health':
          $subjects[$k] = 'epidemiology-global-health';
          break;
        case 'Ecology':
          $subjects[$k] = 'ecology';
          break;
        case 'Developmental biology & stem cells':
          $subjects[$k] = 'developmental-biology-stem-cells';
          break;
        case 'Computational & systems biology':
          $subjects[$k] = 'computational-systems-biology';
          break;
        case 'Cell biology':
          $subjects[$k] = 'cell-biology';
          break;
        case 'Cancer biology':
          $subjects[$k] = 'cancer-biology';
          break;
        case 'Biophysics & structural biology':
          $subjects[$k] = 'biophysics-structural-biology';
          break;
        case 'Biochemistry':
          $subjects[$k] = 'biochemistry';
          break;
        default:
          unset($subjects[$k]);
      }
    }

    return array_values($subjects);
  }

  /**
   * Perform cleanup on focuses and organisms so we can match them with terms
   * that have already been migrated and cleaned up.
   *
   * @param array $values
   * @param boolean $cleanup_string
   * @return array
   */
  public function cleanupFocusesAndOrganisms($values, $cleanup_string = TRUE) {
    $cleaned_values = [];
    foreach ($values as $k => $value) {
      if ($cleanup_string) {
        $value = $this->cleanupString($value);
      }
      if (!in_array($value, $cleaned_values)) {
        $cleaned_values[] = $value;
      }
    }

    return $cleaned_values;
  }

  /**
   * Gather research focuses from the profile description text.
   *
   * @param string $profile
   * @return array
   */
  public function gatherFocusesFromProfile($profile) {
    $profile = strtolower(strip_tags($profile));
    $profile = $this->cleanupString($profile, ['/\n/', '/major subject area\(s\)/', '/keyword([^s])/'], ['', 'keywords', 'keywords$1']);
    $focuses = [];

    if (preg_match('/^.*keywords(?P<focuses>.*)$/', $profile, $match)) {
      $delimiter = '||';
      $found = trim(preg_replace('/\s*:/', '', $match['focuses']));

      $map = [
        'arabidopsis, brachypodium, natural variation, developmental cell biology' => 'arabidopsis;brachypodium;natural variation;developmental cell biology',
      ];

      if (isset($map[$found])) {
        $found = $map[$found];
      }

      $found = preg_replace('/;/', $delimiter, $found);
      $focuses = $this->cleanupFocusesAndOrganisms(explode($delimiter, $found), FALSE);
    }

    return $focuses;
  }

  /**
   * Cleanup research focus or research organism string.
   *
   * @param string $string
   * @param array $search
   * @param array $replace
   * @param boolean $comma_delimiter
   * @return string
   */
  public function cleanupString($string, $search = [], $replace = [], $comma_delimiter = FALSE) {
    if ($comma_delimiter) {
      $detect_delimiter = '/[^a-z0-9\(]*(;|,)[^a-z0-9\(]*/';
    }
    else {
      $detect_delimiter = '/[^a-z0-9\(]*;[^a-z0-9\(]*/';
    }
    $search = array_merge($search, ['/&nbsp;/', '/&#x2013;/', '/\-+/', '/\s+&\s+/', $detect_delimiter, '/^[^a-z0-9\(<\"]+/', '/[^a-z0-9\)>\"]+$/', '/([^a-z0-9\(\-\'\"\/])[^a-z0-9\(\/\-]+([^a-z0-9\(\-\'\"\/])/', '/\s+/']);
    $replace = array_merge($replace, [' ', '-', '-', ' and ', ';', '', '', ' ', '$1 $2', ' ']);
    $string = preg_replace($search, $replace, strtolower(trim($string)));

    $map = [
      'cell cell interaction' => 'cell-cell interaction',
      'drosophila neuroscience' => '<i>drosophila</i> neuroscience',
      'microbe microbe communication' => 'microbe-microbe communication',
      'plant parasite interactions' => 'plant-parasite interactions',
      'plant insect interactions' => 'plant-insect interactions',
      'plant microbe interactions' => 'plant-microbe interactions',
      'plant microbe symbioses' => 'plant-microbe symbioses',
      'protein nucleic acid interactions' => 'protein-nucleic acid interactions',
      'cytoskeleton - membrane interplay' => 'cytoskeleton-membrane interplay',
    ];

    if (isset($map[$string])) {
      $string = $map[$string];
    }

    return $string;
  }

}
