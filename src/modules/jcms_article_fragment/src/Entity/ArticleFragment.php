<?php

namespace Drupal\jcms_article_fragment\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Article fragment entity.
 *
 * @ingroup jcms_article_fragment
 *
 * @ContentEntityType(
 *   id = "article_fragment",
 *   label = @Translation("Article fragment"),
 *   handlers = {
 *     "storage" = "Drupal\jcms_article_fragment\ArticleFragmentStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jcms_article_fragment\ArticleFragmentListBuilder",
 *     "views_data" = "Drupal\jcms_article_fragment\Entity\ArticleFragmentViewsData",
 *     "translation" = "Drupal\jcms_article_fragment\ArticleFragmentTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\jcms_article_fragment\Form\ArticleFragmentForm",
 *       "add" = "Drupal\jcms_article_fragment\Form\ArticleFragmentForm",
 *       "edit" = "Drupal\jcms_article_fragment\Form\ArticleFragmentForm",
 *       "__delete" = "Drupal\jcms_article_fragment\Form\ArticleFragmentDeleteForm",
 *     },
 *     "access" = "Drupal\jcms_article_fragment\ArticleFragmentAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\jcms_article_fragment\ArticleFragmentHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "article_fragment",
 *   data_table = "article_fragment_field_data",
 *   revision_table = "article_fragment_revision",
 *   revision_data_table = "article_fragment_field_revision",
 *   translatable = TRUE,
 *   admin_permission = "administer article fragment entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/article_fragment/{article_fragment}",
 *     "add-form" = "/admin/structure/article_fragment/add",
 *     "edit-form" = "/admin/structure/article_fragment/{article_fragment}/edit",
 *     "__delete-form" = "/admin/structure/article_fragment/{article_fragment}/delete",
 *     "version-history" = "/admin/structure/article_fragment/{article_fragment}/revisions",
 *     "revision" = "/admin/structure/article_fragment/{article_fragment}/revisions/{article_fragment_revision}/view",
 *     "revision_revert" = "/admin/structure/article_fragment/{article_fragment}/revisions/{article_fragment_revision}/revert",
 *     "translation_revert" = "/admin/structure/article_fragment/{article_fragment}/revisions/{article_fragment_revision}/revert/{langcode}",
 *     "__revision_delete" = "/admin/structure/article_fragment/{article_fragment}/revisions/{article_fragment_revision}/delete",
 *     "collection" = "/admin/structure/article_fragment",
 *   },
 *   field_ui_base_route = "article_fragment.settings"
 * )
 */
class ArticleFragment extends RevisionableContentEntityBase implements ArticleFragmentInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += array(
      'user_id' => \Drupal::currentUser()->id(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    foreach (array_keys($this->getTranslationLanguages()) as $langcode) {
      $translation = $this->getTranslation($langcode);

      // If no owner has been set explicitly, make the anonymous user the owner.
      if (!$translation->getOwner()) {
        $translation->setOwnerId(0);
      }
    }

    // If no revision author has been set explicitly, make the article_fragment owner the
    // revision author.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? TRUE : FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionCreationTime() {
    return $this->get('revision_timestamp')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionCreationTime($timestamp) {
    $this->set('revision_timestamp', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionUser() {
    return $this->get('revision_uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionUserId($uid) {
    $this->set('revision_uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Article ID'))
      ->setDescription(t('The name of the Article fragment entity.'))
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setSettings(array(
        'max_length' => 50,
        'text_processing' => 0,
      ))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['image'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Thumbnail image'))
      ->setDescription(t('Thumbnail image field'))
      ->setRevisionable(TRUE)
      ->setRequired(FALSE)
      ->setSettings([
        'file_directory' => 'article_fragments',
        'alt_field_required' => FALSE,
        'file_extensions' => 'png jpg jpeg gif jif tif tiff',
        'min_resolution' => '1800x900'
      ])
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'default',
        'weight' => 0,
      ))
      ->setDisplayOptions('form', array(
        'label' => 'hidden',
        'type' => 'image_image',
        'weight' => 0,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['use_as_banner'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Use the thumbnail image as the banner image.'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', array(
        'type' => 'boolean_checkbox',
        'settings' => array(
          'display_label' => TRUE,
        ),
        'weight' => 1,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['banner_image'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Banner image'))
      ->setDescription(t('Banner image field'))
      ->setRevisionable(TRUE)
      ->setRequired(FALSE)
      ->setSettings([
        'file_directory' => 'article_fragments',
        'alt_field_required' => FALSE,
        'file_extensions' => 'png jpg jpeg gif jif tif tiff',
        'min_resolution' => '1800x900'
      ])
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'default',
        'weight' => 2,
      ))
      ->setDisplayOptions('form', array(
        'label' => 'hidden',
        'type' => 'image_image',
        'weight' => 2,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Article fragment entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'hidden',
        'weight' => 5,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Article fragment is published.'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['revision_timestamp'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Revision timestamp'))
      ->setDescription(t('The time that the current revision was created.'))
      ->setQueryable(FALSE)
      ->setRevisionable(TRUE);

    $fields['revision_uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Revision user ID'))
      ->setDescription(t('The user ID of the author of the current revision.'))
      ->setSetting('target_type', 'user')
      ->setQueryable(FALSE)
      ->setRevisionable(TRUE);

    $fields['revision_translation_affected'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Revision translation affected'))
      ->setDescription(t('Indicates if the last edit of a translation belongs to current revision.'))
      ->setReadOnly(TRUE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    return $fields;
  }

}
