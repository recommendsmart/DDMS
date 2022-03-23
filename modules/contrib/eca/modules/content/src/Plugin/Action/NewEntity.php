<?php

namespace Drupal\eca_content\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\eca\Plugin\Action\ConfigurableActionBase;
use Drupal\eca\Plugin\OptionsInterface;
use Drupal\eca\Service\Conditions;
use Drupal\eca_content\EntityTypeTrait;

/**
 * Create a new content entity without saving it.
 *
 * @Action(
 *   id = "eca_new_entity",
 *   label = @Translation("Entity: create new"),
 *   type = "entity"
 * )
 */
class NewEntity extends ConfigurableActionBase implements OptionsInterface {

  use EntityTypeTrait;

  /**
   * The instantiated entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface|null
   */
  protected ?EntityInterface $entity;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'token_name' => '',
      'type' => '',
      'langcode' => '',
      'label' => '',
      'published' => FALSE,
      'owner' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['token_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Token name'),
      '#default_value' => $this->configuration['token_name'],
      '#weight' => -10,
    ];
    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type'),
      '#options' => $this->getOptions('type'),
      '#default_value' => $this->configuration['type'],
      '#weight' => -9,
    ];
    $form['langcode'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Language code'),
      '#default_value' => $this->configuration['langcode'],
      '#weight' => -8,
    ];
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Entity label'),
      '#default_value' => $this->configuration['label'],
      '#weight' => -7,
    ];
    $form['published'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Published'),
      '#default_value' => $this->shouldPublish(),
      '#weight' => -6,
    ];
    $form['owner'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Owner UID'),
      '#default_value' => $this->configuration['owner'],
      '#weight' => -5,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['token_name'] = $form_state->getValue('token_name');
    $this->configuration['type'] = $form_state->getValue('type');
    $this->configuration['langcode'] = $form_state->getValue('langcode');
    $this->configuration['label'] = $form_state->getValue('label');
    $this->configuration['published'] = $form_state->getValue('published');
    $this->configuration['owner'] = $form_state->getValue('owner');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions(string $id): ?array {
    if ($id === 'type') {
      $types = [];
      $this->bundleField(FALSE, FALSE);
      foreach (static::$typesAndBundles as $info) {
        $types[$info['value']] = $info['name'];
      }
      return $types;
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\Core\Access\AccessResultInterface $access_result */
    $access_result = parent::access($object, $account, TRUE);
    if ($access_result->isAllowed() && !empty($this->configuration['type'])) {
      $account = $account ?? $this->currentUser;
      list($entity_type_id, $bundle) = array_pad(explode(' ', $this->configuration['type'], 2), 2, NULL);
      if ($bundle === NULL || $bundle === '' || $bundle === '_all') {
        $access_result = AccessResult::forbidden(sprintf('Cannot determine access without a specified bundle.', $entity_type_id));
      }
      elseif (!$this->entityTypeManager->hasDefinition($entity_type_id)) {
        // @todo This should be taken care of by a submit validation handler.
        $access_result = AccessResult::forbidden(sprintf('Cannot determine access when "%s" is not a valid entity type ID.', $entity_type_id));
      }
      elseif (!$this->entityTypeManager->hasHandler($entity_type_id, 'access')) {
        $access_result = AccessResult::forbidden('Cannot determine access without an access handler.');
      }
      else {
        /** @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface $access_handler */
        $access_handler = $this->entityTypeManager->getHandler($entity_type_id, 'access');
        $access_result = $access_handler->createAccess($bundle, $account, [], TRUE);
      }
    }
    return $return_as_object ? $access_result : $access_result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    $config = &$this->configuration;
    list($entity_type_id, $bundle) = explode(' ', $config['type']);
    $values = [];
    $definition = $this->entityTypeManager->getDefinition($entity_type_id);
    $entity_keys = $definition->get('entity_keys');
    if (isset($entity_keys['bundle'])) {
      $values[$entity_keys['bundle']] = $bundle;
    }
    if (isset($entity_keys['langcode'])) {
      $langcode = in_array(trim($config['langcode']), ['_interface', '']) ? \Drupal::languageManager()->getCurrentLanguage()->getId() : trim($config['langcode']);
      $values[$entity_keys['langcode']] = $langcode;
    }
    if (isset($entity_keys['label']) && isset($config['label'])) {
      $values[$entity_keys['label']] = trim($this->tokenServices->replace($config['label'], [], ['clear' => TRUE]));
    }
    if (isset($entity_keys['published'])) {
      $values[$entity_keys['published']] = (int) $this->shouldPublish();
    }
    if (isset($entity_keys['owner'])) {
      if (!empty($config['owner'])) {
        $owner_id = trim($this->tokenServices->replace($config['owner'], [], ['clear' => TRUE]));
      }
      if (!isset($owner_id) || $owner_id === '') {
        $owner_id = $this->currentUser->id();
      }
      $values[$entity_keys['owner']] = $owner_id;
    }
    $entity = $this->entityTypeManager->getStorage($entity_type_id)->create($values) ?: NULL;
    $this->entity = $entity;
    $this->tokenServices->addTokenData($config['token_name'], $entity);
  }

  /**
   * Whether the new entity should be set as published or not.
   *
   * @return bool
   *   Returns TRUE in case to set as published, FALSE otherwise.
   */
  protected function shouldPublish(): bool {
    return $this->configuration['published'] === Conditions::OPTION_YES;
  }

}
