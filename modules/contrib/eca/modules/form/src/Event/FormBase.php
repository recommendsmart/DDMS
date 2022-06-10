<?php

namespace Drupal\eca_form\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Form\BaseFormIdInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Event\ConditionalApplianceInterface;
use Drupal\eca\Event\FormEventInterface;

/**
 * Abstract base class for form events.
 *
 * @package Drupal\eca_form\Event
 */
abstract class FormBase extends Event implements ConditionalApplianceInterface, FormEventInterface {

  /**
   * The form array.
   *
   * This may be the complete form, or a sub-form, or a specific form element.
   *
   * @var array
   */
  protected array $form;

  /**
   * The form state.
   *
   * @var \Drupal\Core\Form\FormStateInterface
   */
  protected FormStateInterface $formState;

  /**
   * Constructs a FormBase instance.
   *
   * @param array &$form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function __construct(array &$form, FormStateInterface $form_state) {
    $this->form = &$form;
    $this->formState = $form_state;
  }

  /**
   * {@inheritdoc}
   */
  public function appliesForLazyLoadingWildcard(string $wildcard): bool {
    [$w_form_ids, $w_entity_type_ids, $w_bundles, $w_operations] = explode(':', $wildcard);
    $form_object = $this->getFormState()->getFormObject();

    if ($w_form_ids !== '*') {
      $form_ids = [$form_object->getFormId()];
      if ($form_object instanceof BaseFormIdInterface) {
        $form_ids[] = $form_object->getBaseFormId();
      }

      if (empty(array_intersect($form_ids, explode(',', $w_form_ids)))) {
        return FALSE;
      }
    }

    /** @var \Drupal\Core\Entity\EntityFormInterface $form_object */
    $is_entity_form = ($form_object instanceof EntityFormInterface);

    if ($w_entity_type_ids !== '*') {
      if (!$is_entity_form) {
        return FALSE;
      }
      if (!in_array($form_object->getEntity()->getEntityTypeId(), explode(',', $w_entity_type_ids))) {
        return FALSE;
      };
    }

    if ($w_bundles !== '*') {
      if (!$is_entity_form) {
        return FALSE;
      }
      if (!in_array($form_object->getEntity()->bundle(), explode(',', $w_bundles))) {
        return FALSE;
      };
    }

    if ($w_operations !== '*') {
      if (!$is_entity_form) {
        return FALSE;
      }
      if (!in_array($form_object->getOperation(), explode(',', $w_operations))) {
        return FALSE;
      };
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(string $id, array $arguments): bool {
    $form_object = $this->getFormState()->getFormObject();

    if (!empty($arguments['form_id']) && $arguments['form_id'] !== '*') {
      $form_ids = [$form_object->getFormId()];
      if ($form_object instanceof BaseFormIdInterface) {
        $form_ids[] = $form_object->getBaseFormId();
      }

      $contains_form_id = FALSE;
      foreach (explode(',', $arguments['form_id']) as $c_form_id) {
        $c_form_id = strtolower(trim(str_replace('-', '_', $c_form_id)));
        if ($contains_form_id = in_array($c_form_id, $form_ids)) {
          break;
        }
      }
      if (!$contains_form_id) {
        return FALSE;
      }
    }

    /** @var \Drupal\Core\Entity\EntityFormInterface $form_object */
    $is_entity_form = ($form_object instanceof EntityFormInterface);

    if (!empty($arguments['entity_type_id']) && $arguments['entity_type_id'] !== '*') {
      if (!$is_entity_form) {
        return FALSE;
      }

      $contains_entity_type_id = FALSE;
      foreach (explode(',', $arguments['entity_type_id']) as $c_entity_type_id) {
        $c_entity_type_id = strtolower(trim($c_entity_type_id));
        if ($contains_entity_type_id = ($c_entity_type_id === $form_object->getEntity()->getEntityTypeId())) {
          break;
        }
      }
      if (!$contains_entity_type_id) {
        return FALSE;
      }
    }

    if (!empty($arguments['bundle']) && $arguments['bundle'] !== '*') {
      if (!$is_entity_form) {
        return FALSE;
      }

      $contains_bundle = FALSE;
      foreach (explode(',', $arguments['bundle']) as $c_bundle) {
        $c_bundle = strtolower(trim($c_bundle));
        if ($contains_bundle = ($c_bundle === $form_object->getEntity()->bundle())) {
          break;
        }
      }
      if (!$contains_bundle) {
        return FALSE;
      }
    }

    if (!empty($arguments['operation']) && $arguments['operation'] !== '*') {
      if (!$is_entity_form) {
        return FALSE;
      }

      $contains_operation = FALSE;
      foreach (explode(',', $arguments['operation']) as $c_operation) {
        $c_operation = trim($c_operation);
        if ($contains_operation = ($c_operation === $form_object->getOperation())) {
          break;
        }
      }
      if (!$contains_operation) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function &getForm(): array {
    return $this->form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormState(): FormStateInterface {
    return $this->formState;
  }

}
