<?php

namespace Drupal\jcms_migrate\Plugin\migrate\process;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\migrate\process\Migration;
use Drupal\migrate\Plugin\MigratePluginManager;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\paragraphs\Entity\Paragraph;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Process the research detail values into paragraphs.
 *
 * @MigrateProcessPlugin(
 *   id = "jcms_research_details"
 * )
 */
class JCMSResearchDetails extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The process plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigratePluginManager
   */
  protected $processPluginManager;

  /**
   * The migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, MigrationPluginManagerInterface $migration_plugin_manager, MigratePluginManager $process_plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->migrationPluginManager = $migration_plugin_manager;
    $this->migration = $migration;
    $this->processPluginManager = $process_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('plugin.manager.migration'),
      $container->get('plugin.manager.migrate.process')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    list($expertises, $focus_ids, $organism_ids) = $value;
    $expertises = !empty($expertises) ? $this->subjectSourceIDs(explode(',', $expertises)) : [];
    $focus_ids = !empty($focus_ids) ? explode(',', $focus_ids) : [];
    $organism_ids = !empty($organism_ids) ? explode(',', $organism_ids) : [];

    // Convert source_ids to destination_ids.
    $dest_expertises = $this->migrationDestionationIDs('jcms_subjects_json', $expertises, $migrate_executable, $row, $destination_property);
    $dest_focus_ids = $this->migrationDestionationIDs('jcms_research_focuses_db', $focus_ids, $migrate_executable, $row, $destination_property);
    $dest_organism_ids = $this->migrationDestionationIDs('jcms_research_organisms_db', $organism_ids, $migrate_executable, $row, $destination_property);

    $values = [];
    if ($dest_expertises) {
      $values['field_research_expertises'] = [];
      foreach ($dest_expertises as $dest_expertise) {
        $values['field_research_expertises'][] = [
          'target_id' => $dest_expertise,
        ];
      }
    }
    if ($dest_focus_ids) {
      $values['field_research_focuses'] = [];
      foreach ($dest_focus_ids as $dest_focus_id) {
        $values['field_research_focuses'][] = [
          'target_id' => $dest_focus_id,
        ];
      }
    }
    if ($dest_organism_ids) {
      $values['field_research_organisms'] = [];
      foreach ($dest_organism_ids as $dest_organism_id) {
        $values['field_research_organisms'][] = [
          'target_id' => $dest_organism_id,
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
   * Convert source ids to migration destination ids.
   *
   * @param string $migration_id
   * @param array $values
   * @param \Drupal\migrate\MigrateExecutableInterface $migrate_executable
   * @param \Drupal\migrate\Row $row
   * @param string $destination_property
   * @return array|null
   */
  protected function migrationDestionationIDs($migration_id, $values, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!empty($values)) {
      $migration = new Migration(['migration' => $migration_id], $this->pluginId, $this->pluginDefinition, $this->migration, $this->migrationPluginManager, $this->processPluginManager);
      $dest_values = [];
      foreach ($values as $value) {
        if ($dest_value = $migration->transform([$value], $migrate_executable, $row, $destination_property)) {
          $dest_values[]  = $dest_value[0];
        }
      }
      if (!empty($dest_values)) {
        return $dest_values;
      }
    }

    return NULL;
  }

  /**
   * Return an array of source ids for the subjects of expertise.
   *
   * @param array $subjects
   * @return array
   */
  protected function subjectSourceIDs($subjects) {
    foreach ($subjects as $k => $subject) {
      switch ($subject) {
        case 'Plant biology':
          $subjects[$k] = 'plant-biology';
          break;
        case 'Neuroscience':
          $subjects[$k] = 'neuroscience';
          break;
        case 'Microbiology & infectious disease':
          $subjects[$k] = 'microbiology-and-infectious-disease';
          break;
        case 'Immunology':
          $subjects[$k] = 'immunology';
          break;
        case 'Human biology & medicine':
          $subjects[$k] = 'human-biology-and-medicine';
          break;
        case 'Genomics & evolutionary biology':
          $subjects[$k] = 'genomics-and-evolutionary-biology';
          break;
        case 'Genes & chromosomes':
          $subjects[$k] = 'genes-and-chromosomes';
          break;
        case 'Epidemiology & global health':
          $subjects[$k] = 'epidemiology-and-global-health';
          break;
        case 'Ecology':
          $subjects[$k] = 'ecology';
          break;
        case 'Developmental biology & stem cells':
          $subjects[$k] = 'developmental-biology-and-stem-cells';
          break;
        case 'Computational & systems biology':
          $subjects[$k] = 'computational-and-systems-biology';
          break;
        case 'Cell biology':
          $subjects[$k] = 'cell-biology';
          break;
        case 'Cancer biology':
          $subjects[$k] = 'cancer-biology';
          break;
        case 'Biophysics & structural biology':
          $subjects[$k] = 'biophysics-and-structural-biology';
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

}