<?php

namespace Drupal\jcms_migrate\Plugin\migrate\source;

/**
 * Source plugin for labs_experiment content.
 *
 * @MigrateSource(
 *   id = "jcms_labs_experiment_node"
 * )
 */
class JCMSLabsExperimentNode extends JCMSBlogArticleNode {

  /**
   * @var array
   */
  protected $terms = ['labs'];

  /**
   * @var array
   */
  protected $excludeTerms = [];

  /**
   * @var bool
   */
  protected $nullTerms = FALSE;

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Reset counter.
    $this->getDatabase()->query('SELECT @counter:=null')->execute();
    $query = parent::query();
    $query->orderBy('n.created', 'ASC');
    $query->addExpression('if(isnull(@counter), @counter:=1, @counter:=@counter + 1)', 'number');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = parent::fields();
    $fields['number'] = $this->t('Experiment number');
    return $fields;
  }

}
