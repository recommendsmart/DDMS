<?php

namespace Drupal\entity_reference_revisions;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\RevisionableStorageInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldItemListTranslationChangesInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;

/**
 * Defines a item list class for entity reference fields.
 */
class EntityReferenceRevisionsFieldItemList extends EntityReferenceFieldItemList implements EntityReferenceFieldItemListInterface {

  /**
   * {@inheritdoc}
   */
  public function referencedEntities() {
    if (empty($this->list)) {
      return array();
    }

    // Collect the IDs of existing entities to load, and directly grab the
    // "autocreate" entities that are already populated in $item->entity.
    $target_entities = $ids = array();
    foreach ($this->list as $delta => $item) {
      if ($item->hasNewEntity()) {
        $target_entities[$delta] = $item->entity;
      }
      elseif ($item->target_revision_id !== NULL) {
        $ids[$delta] = $item->target_revision_id;
      }
    }

    // Load and add the existing entities.
    if ($ids) {
      $target_type = $this->getFieldDefinition()->getSetting('target_type');
      $storage = \Drupal::entityTypeManager()->getStorage($target_type);

      // Aim to load all revisions in bulk if storage type supports it.
      if ($storage instanceof RevisionableStorageInterface) {
        $entities = $storage->loadMultipleRevisions($ids);
      }

      foreach ($ids as $delta => $target_id) {
        $entity = $entities[$target_id] ?? $storage->loadRevision($target_id);
        if ($entity) {
          $target_entities[$delta] = $entity;
        }
      }
      // Ensure the returned array is ordered by deltas.
      ksort($target_entities);
    }

    return $target_entities;
  }

  /**
   * {@inheritdoc}
   */
  public static function processDefaultValue($default_value, FieldableEntityInterface $entity, FieldDefinitionInterface $definition) {
    $default_value = parent::processDefaultValue($default_value, $entity, $definition);

    if ($default_value) {
      // Convert UUIDs to numeric IDs.
      $uuids = array();
      foreach ($default_value as $delta => $properties) {
        if (isset($properties['target_uuid'])) {
          $uuids[$delta] = $properties['target_uuid'];
        }
      }
      if ($uuids) {
        $target_type = $definition->getSetting('target_type');
        $entity_ids = \Drupal::entityQuery($target_type)
          ->condition('uuid', $uuids, 'IN')
          ->execute();
        $entities = \Drupal::entityTypeManager()
          ->getStorage($target_type)
          ->loadMultiple($entity_ids);

        $entity_uuids = array();
        foreach ($entities as $id => $entity) {
          $entity_uuids[$entity->uuid()] = $id;
        }
        foreach ($uuids as $delta => $uuid) {
          if (isset($entity_uuids[$uuid])) {
            $default_value[$delta]['target_id'] = $entity_uuids[$uuid];
            unset($default_value[$delta]['target_uuid']);
          }
          else {
            unset($default_value[$delta]);
          }
        }
      }

      // Ensure we return consecutive deltas, in case we removed unknown UUIDs.
      $default_value = array_values($default_value);
    }

    return $default_value;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultValuesFormSubmit(array $element, array &$form, FormStateInterface $form_state) {
    $default_value = parent::defaultValuesFormSubmit($element, $form, $form_state);

    // Convert numeric IDs to UUIDs to ensure config deployability.
    $ids = array();
    foreach ($default_value as $delta => $properties) {
      $ids[] = $properties['target_revision_id'];
    }

    $entities = array();
    foreach($ids as $id) {
      $entities[$id] = \Drupal::entityTypeManager()
        ->getStorage($this->getSetting('target_type'))
        ->loadRevision($id);
    }

    foreach ($default_value as $delta => $properties) {
      $default_value[$delta] = array(
        'target_uuid' => $entities[$properties['target_revision_id']]->uuid(),
        'target_revision_id' => $properties['target_revision_id'],
      );
    }
    return $default_value;
  }

  /**
   * {@inheritdoc}
   */
  public function hasAffectingChanges(FieldItemListInterface $original_items, $langcode) {
    // If there are fewer items, then it is a change.
    if (count($this) < count($original_items)) {
      return TRUE;
    }

    foreach ($this as $delta => $item) {
      // If this is a different entity, then it is an affecting change.
      if (!$original_items->offsetExists($delta) || $item->target_id != $original_items[$delta]->target_id) {
        return TRUE;
      }
      // If it is the same entity, only consider it as having affecting changes
      // if the target entity itself has changes.
      if ($item->entity && $item->entity->hasTranslation($langcode) && $item->entity->getTranslation($langcode)->hasTranslationChanges()) {
        return TRUE;
      }
    }

    return FALSE;
  }

}
