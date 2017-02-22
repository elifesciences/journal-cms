<?php

//namespace Drupal\jcms_article_fragment\Form;
//
//use Drupal\Core\Database\Connection;
//use Drupal\Core\Entity\EntityStorageInterface;
//use Drupal\Core\Form\ConfirmFormBase;
//use Drupal\Core\Form\FormStateInterface;
//use Drupal\Core\Url;
//use Symfony\Component\DependencyInjection\ContainerInterface;
//
///**
// * Provides a form for deleting a Article fragment revision.
// *
// * @ingroup jcms_article_fragment
// */
//class ArticleFragmentRevisionDeleteForm extends ConfirmFormBase {
//
//
//  /**
//   * The Article fragment revision.
//   *
//   * @var \Drupal\jcms_article_fragment\Entity\ArticleFragmentInterface
//   */
//  protected $revision;
//
//  /**
//   * The Article fragment storage.
//   *
//   * @var \Drupal\Core\Entity\EntityStorageInterface
//   */
//  protected $ArticleFragmentStorage;
//
//  /**
//   * The database connection.
//   *
//   * @var \Drupal\Core\Database\Connection
//   */
//  protected $connection;
//
//  /**
//   * Constructs a new ArticleFragmentRevisionDeleteForm.
//   *
//   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
//   *   The entity storage.
//   * @param \Drupal\Core\Database\Connection $connection
//   *   The database connection.
//   */
//  public function __construct(EntityStorageInterface $entity_storage, Connection $connection) {
//    $this->ArticleFragmentStorage = $entity_storage;
//    $this->connection = $connection;
//  }
//
//  /**
//   * {@inheritdoc}
//   */
//  public static function create(ContainerInterface $container) {
//    $entity_manager = $container->get('entity.manager');
//    return new static(
//      $entity_manager->getStorage('article_fragment'),
//      $container->get('database')
//    );
//  }
//
//  /**
//   * {@inheritdoc}
//   */
//  public function getFormId() {
//    return 'article_fragment_revision_delete_confirm';
//  }
//
//  /**
//   * {@inheritdoc}
//   */
//  public function getQuestion() {
//    return t('Are you sure you want to delete the revision from %revision-date?', array('%revision-date' => format_date($this->revision->getRevisionCreationTime())));
//  }
//
//  /**
//   * {@inheritdoc}
//   */
//  public function getCancelUrl() {
//    return new Url('entity.article_fragment.version_history', array('article_fragment' => $this->revision->id()));
//  }
//
//  /**
//   * {@inheritdoc}
//   */
//  public function getConfirmText() {
//    return t('Delete');
//  }
//
//  /**
//   * {@inheritdoc}
//   */
//  public function buildForm(array $form, FormStateInterface $form_state, $article_fragment_revision = NULL) {
//    $this->revision = $this->ArticleFragmentStorage->loadRevision($article_fragment_revision);
//    $form = parent::buildForm($form, $form_state);
//
//    return $form;
//  }
//
//  /**
//   * {@inheritdoc}
//   */
//  public function submitForm(array &$form, FormStateInterface $form_state) {
//    $this->ArticleFragmentStorage->deleteRevision($this->revision->getRevisionId());
//
//    $this->logger('content')->notice('Article fragment: deleted %title revision %revision.', array('%title' => $this->revision->label(), '%revision' => $this->revision->getRevisionId()));
//    drupal_set_message(t('Revision from %revision-date of Article fragment %title has been deleted.', array('%revision-date' => format_date($this->revision->getRevisionCreationTime()), '%title' => $this->revision->label())));
//    $form_state->setRedirect(
//      'entity.article_fragment.canonical',
//       array('article_fragment' => $this->revision->id())
//    );
//    if ($this->connection->query('SELECT COUNT(DISTINCT vid) FROM {article_fragment_field_revision} WHERE id = :id', array(':id' => $this->revision->id()))->fetchField() > 1) {
//      $form_state->setRedirect(
//        'entity.article_fragment.version_history',
//         array('article_fragment' => $this->revision->id())
//      );
//    }
//  }
//
//}
