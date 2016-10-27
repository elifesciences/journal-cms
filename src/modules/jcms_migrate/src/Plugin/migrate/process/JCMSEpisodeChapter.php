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
 * Process the episode chapter values into paragraphs.
 *
 * @MigrateProcessPlugin(
 *   id = "jcms_episode_chapter"
 * )
 */
class JCMSEpisodeChapter extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  use JMCSCheckMarkupTrait;

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
    if (!empty($value)) {
      $values = [
        'type' => 'episode_chapter',
        'field_block_title' => [
          'value' => $this->checkMarkup($value['title']),
        ],
        'field_chapter_time' => [
          'value' => $value['time'],
        ],
      ];

      if (!empty($value['impact_statement'])) {
        $values['field_block_html'] = [
          'value' => $this->checkMarkup($value['impact_statement']),
          'format' => 'basic_html',
        ];
      }

      if (!empty($value['articles'])) {
        $values['field_chapter_content'] = [];
        foreach ($value['articles'] as $article) {
          $values['field_chapter_content'][] = [
            'value' => $article,
          ];
        }
      }

      $paragraph = Paragraph::create($values);
      $paragraph->save();
      return [
        'target_id' => $paragraph->id(),
        'target_revision_id' => $paragraph->getRevisionId(),
      ];
    }
    return NULL;
  }

}
