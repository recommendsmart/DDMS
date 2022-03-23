<?php

namespace Drupal\eca_content\Plugin\ECA\Condition;

use Drupal\Core\Entity\EntityInterface;

/**
 * Plugin implementation of the ECA condition for an entity original field value.
 *
 * @EcaCondition(
 *   id = "eca_entity_original_field_value",
 *   label = @Translation("Entity: original has field value"),
 *   context_definitions = {
 *     "entity" = @ContextDefinition("entity", label = @Translation("Entity"))
 *   }
 * )
 */
class EntityOriginalFieldValue extends EntityFieldValue {

  /**
   * {@inheritdoc}
   */
  public function getEntity(): ?EntityInterface {
    if (!($entity = parent::getEntity())) {
      return NULL;
    }
    return $entity->original ?? NULL;
  }

}
