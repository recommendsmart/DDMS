<?php

namespace Drupal\eca\Entity;

use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;

/**
 * Defines a class to build a listing of ECA config entities.
 *
 * @see \Drupal\eca\Entity\Eca
 */
class ListBuilder extends DraggableListBuilder {

  /**
   * {@inheritdoc}
   */
  protected $entitiesKey = 'eca_entities';

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * This flag stores the calculated result for ::showModeller().
   *
   * @var bool|null
   */
  protected ?bool $showModeller;

  /**
   * Constructs a new ListBuilder.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, MessengerInterface $messenger) {
    parent::__construct($entity_type, $storage);
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'eca_collection';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['model'] = $this->t('Model');
    if ($this->showModeller()) {
      $header['modeller'] = $this->t('Modeller');
    }
    $header['events'] = $this->t('Events');
    $header['version'] = $this->t('Version');
    $header['status'] = $this->t('Enabled');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\eca\Entity\Eca $eca */
    $eca = $entity;

    $events = [];
    foreach ($eca->getUsedEvents() as $used_event) {
      $plugin = $used_event->getPlugin();
      $event_info = $plugin->getPluginDefinition()['label'];
      // If available, additionally display the first config value of the event.
      if ($event_config = $used_event->getConfiguration()) {
        $first_key = key($event_config);
        $first_value = current($event_config);
        foreach ($plugin->fields() as $plugin_field) {
          if (isset($plugin_field['name']) && ($plugin_field['name'] === $first_key) && isset($plugin_field['extras']['choices'])) {
            foreach ($plugin_field['extras']['choices'] as $choice) {
              if (isset($choice['name'], $choice['value']) && $choice['value'] === $first_value) {
                $first_value = $choice['name'];
                break 2;
              }
            }
          }
        }
        $event_info .= ' (' . $first_value . ')';
      }
      $events[] = $event_info;
    }

    $row['model'] = ['#markup' => $eca->label() ?: $eca->id()];
    if ($this->showModeller()) {
      $row['modeller'] = ['#markup' => (string) $eca->get('modeller')];
    }
    $row['events'] = ['#theme' => 'item_list', '#items' => $events];
    $row['version'] = ['#markup' => $eca->get('version') ?: $this->t('undefined')];
    $row['status'] = [
      '#markup' => $eca->status() ? $this->t('yes') : $this->t('no'),
    ];

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['actions']['submit']['#value'] = $this->t('Save');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->messenger->addStatus($this->t('The ordering has been saved.'));
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    if ($entity->access('update')) {
      $operations['edit'] = [
        'title' => $this->t('Edit'),
        'weight' => 10,
        'url' => Url::fromRoute('entity.eca.edit_form', ['eca' => $entity->id()]),
      ];
    }

    return $operations;
  }

  /**
   * Determines whether the modeller info should be displayed or not.
   *
   * @return bool
   *   Returns TRUE if the modeller info should be displayed, FALSE otherwise.
   */
  protected function showModeller(): bool {
    if (!isset($this->showModeller)) {
      $modellers = [];
      /** @var \Drupal\eca\Entity\Eca $eca */
      foreach ($this->storage->loadMultiple() as $eca) {
        if ($eca->get('modeller')) {
          $modellers[$eca->get('modeller')] = TRUE;
        }
        else {
          $modellers['_none'] = TRUE;
        }
      }
      $this->showModeller = count($modellers) > 1;
    }
    return $this->showModeller;
  }

}
