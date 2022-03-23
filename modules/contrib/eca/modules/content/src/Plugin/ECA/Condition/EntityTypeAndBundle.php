<?php

namespace Drupal\eca_content\Plugin\ECA\Condition;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Plugin\ECA\Condition\ConditionBase;
use Drupal\eca\Plugin\OptionsInterface;
use Drupal\eca_content\EntityTypeTrait;

/**
 * Plugin implementation of the ECA condition for entity type and bundle.
 *
 * @EcaCondition(
 *   id = "eca_entity_type_bundle",
 *   label = "Entity type and bundle",
 *   context_definitions = {
 *     "entity" = @ContextDefinition("entity", label = @Translation("Entity"))
 *   }
 * )
 */
class EntityTypeAndBundle extends ConditionBase implements OptionsInterface {

  use EntityTypeTrait;

  /**
   * {@inheritdoc}
   */
  public function evaluate(): bool {
    $entity = $this->getContextValue('entity');
    if ($entity instanceof EntityInterface) {
      $result = $this->bundleFieldApplies($entity, $this->configuration['type']);
      return $this->negationCheck($result);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
        'type' => '',
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity type (and bundle)'),
      '#default_value' => $this->configuration['type'],
      '#options' => $this->getOptions('type'),
      '#weight' => -10,
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['type'] = $form_state->getValue('type');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions(string $id): ?array {
    if ($id === 'type') {
      $options = [];
      foreach ($this->bundleField()['extras']['choices'] as $item) {
        $options[$item['value']] = $item['name'];
      }
      return $options;
    }
    return NULL;
  }

}
