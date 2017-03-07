<?php

namespace Drupal\jcms_article_fragment\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\jcms_article_fragment\FragmentApi;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for Article fragment edit forms.
 *
 * @ingroup jcms_article_fragment
 */
class ArticleFragmentForm extends ContentEntityForm {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  protected $api;

  /**
   * Constructs a ContentEntityForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager, FragmentApi $api) {
    $this->entityManager = $entity_manager;
    $this->api = $api;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity.manager'), $container->get('jcms_article_fragment.api'));
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\jcms_article_fragment\Entity\ArticleFragment */
    $form = parent::buildForm($form, $form_state);
    if (!$this->entity->isNew()) {
      $form['new_revision'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Create new revision'),
        '#default_value' => FALSE,
        '#weight' => 10,
      ];
    }
    $entity = $this->entity;
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // Post image fragment to Lax.
    try {
      $this->setImageFragment($form_state);
    }
    catch (\Exception $e) {
      $full_message = $e->getResponse()->getBody()->getContents();
      drupal_set_message(t('An error occurred saving this fragment: @error', ['@error' => $full_message]), 'error');
      $e_message = "Message: $full_message\n";
      $e_line = "Line: {$e->getLine()}\n";
      $e_trace = "Trace: {$e->getTraceAsString()}\n";
      $error = $e_message . $e_line . $e_trace;
      error_log($error);
      return;
    }
    // Save the entity if the fragment call was successful.
    $entity = &$this->entity;
    // Save as a new revision if requested to do so.
    if (!$form_state->isValueEmpty('new_revision') && $form_state->getValue('new_revision') != FALSE) {
      $entity->setNewRevision();
      // If a new revision is created, save the current user as revision author.
      $entity->setRevisionCreationTime(REQUEST_TIME);
      $entity->setRevisionUserId(\Drupal::currentUser()->id());
    }
    else {
      $entity->setNewRevision(FALSE);
    }
    $status = parent::save($form, $form_state);
    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Article fragment.', [
          '%label' => $entity->label(),
        ]));
        break;
      default:
        drupal_set_message($this->t('Saved the %label Article fragment.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.article_fragment.canonical', ['article_fragment' => $entity->id()]);
  }

  /**
   * Sets the image for an article via an image fragment.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  /**
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function setImageFragment(FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $article_id = $values['name'][0]['value'] ?? '';
    $thumb_fid = $values['image'][0]['fids'][0] ?? 0;
    $thumb_alt = $values['image'][0]['alt'] ?? '';
    $banner_fid = $values['banner_image'][0]['fids'][0] ?? 0;
    $banner_alt = $values['banner_image'][0]['alt'] ?? '';
    $use_thumb_as_banner = $values['use_as_banner']['value'] ?? 0;
    $this->api->postImageFragment($article_id, $thumb_fid, $thumb_alt, $banner_fid, $banner_alt, $use_thumb_as_banner);
  }

}
