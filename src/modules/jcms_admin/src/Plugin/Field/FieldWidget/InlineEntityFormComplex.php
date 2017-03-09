<?php

namespace Drupal\jcms_admin\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Complex inline widget.
 *
 * @FieldWidget(
 *   id = "jcms_inline_entity_form_complex",
 *   label = @Translation("Inline entity form - JCMS"),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   multiple_values = true
 * )
 */
class InlineEntityFormComplex extends \Drupal\inline_entity_form\Plugin\Field\FieldWidget\InlineEntityFormComplex {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $entities = $form_state->get(['inline_entity_form', $this->getIefId(), 'entities']);
    foreach ($entities as $key => $value) {
      /** @var \Drupal\Core\Entity\EntityInterface $entity */
      $entity = $value['entity'];
      if (empty($value['form'])) {
        $row = &$element['entities'][$key];
        $moderation_info = \Drupal::service('content_moderation.moderation_information');
        if (!$moderation_info->isLatestRevision($entity)) {
          $latest = $moderation_info->getLatestRevision($entity->getEntityTypeId(), $entity->id());
          $row['#label'] = $this->inlineFormHandler->getEntityLabel($latest) . ' *';
        }
        elseif ($entity->get('status')->value == 0) {
          $row['#label'] .= ' *';
        }

        // Make sure entity_access is not checked for unsaved entities.
        $entity_id = $entity->id();
        if (!empty($entity_id) && $entity->access('update')) {
          $row['actions']['ief_entity_edit'] = [
            '#title' => $this->t('Edit'),
            '#type' => 'link',
            '#url' => $entity->toUrl('edit-form', ['query' => ['destination' => \Drupal::service('path.current')->getPath()]]),
            '#attributes' => ['class' => ['button']],
          ];
        }
        else {
          $row['actions']['ief_entity_edit']['#value'] = $this->t('Modify new');
        }
      }
    }
    return $element;
  }

}
