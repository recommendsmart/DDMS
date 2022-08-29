<?php

namespace Drupal\eca\Entity;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\eca\Entity\Objects\EcaAction;
use Drupal\eca\Entity\Objects\EcaEvent;
use Drupal\eca\Entity\Objects\EcaGateway;
use Drupal\eca\Entity\Objects\EcaObject;
use Drupal\eca\Plugin\ECA\Modeller\ModellerInterface;
use Drupal\eca\Plugin\PluginUsageInterface;

/**
 * Defines the ECA entity type.
 *
 * @ConfigEntityType(
 *   id = "eca",
 *   label = @Translation("ECA"),
 *   label_collection = @Translation("ECAs"),
 *   label_singular = @Translation("ECA"),
 *   label_plural = @Translation("ECAs"),
 *   label_count = @PluralTranslation(
 *     singular = "@count ECA",
 *     plural = "@count ECAs",
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\eca\Entity\EcaStorage",
 *   },
 *   config_prefix = "eca",
 *   admin_permission = "administer eca",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "status" = "status",
 *     "weight" = "weight"
 *   },
 *   config_export = {
 *     "id",
 *     "modeller",
 *     "label",
 *     "uuid",
 *     "status",
 *     "version",
 *     "weight",
 *     "events",
 *     "conditions",
 *     "gateways",
 *     "actions"
 *   }
 * )
 */
class Eca extends ConfigEntityBase implements EntityWithPluginCollectionInterface {

  use EcaTrait;

  /**
   * List of action plugins for which validation needs to be avoided.
   *
   * @var string[]
   *
   * @see https://www.drupal.org/project/eca/issues/3278080
   */
  protected static array $ignoreConfigValidationActions = [
    'action_send_email_action',
    'node_assign_owner_action',
    'eca_tamper:find_replace_regex',
    'eca_tamper:keyword_filter',
    'eca_tamper:math',
  ];

  /**
   * ID of the ECA config entity.
   *
   * @var string
   */
  protected string $id;

  /**
   * Label of the ECA config entity.
   *
   * @var string
   */
  protected string $label;

  /**
   * List of events.
   *
   * @var array
   */
  protected array $events = [];

  /**
   * List of conditions.
   *
   * @var array
   */
  protected array $conditions = [];

  /**
   * List of gateways.
   *
   * @var array|null
   */
  protected ?array $gateways = [];

  /**
   * List of actions.
   *
   * @var array
   */
  protected array $actions = [];

  /**
   * Model config entity for the ECA config entity.
   *
   * @var \Drupal\eca\Entity\Model
   */
  protected Model $model;

  /**
   * Whether this instance s in testing mode.
   *
   * @var bool
   */
  protected static bool $isTesting = FALSE;

  /**
   * Set the instance into testing mode.
   *
   * This will prevent dependency calculation which would fail during test setup
   * if not all dependant config entities were available from the test module
   * itself.
   *
   * Problem is, that we can't add all the config dependencies to the test
   * modules, because that would fail if we enable the test modules in a real
   * Drupal instance, as some of those config entities already exist from
   * core modules.
   */
  public static function setTesting(): void {
    static::$isTesting = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage): void {
    parent::preSave($storage);
    foreach ([
      'events' => $this->eventPluginManager(),
      'conditions' => $this->conditionPluginManager(),
      'actions' => $this->actionPluginManager(),
    ] as $plugins => $manager) {
      foreach ($this->{$plugins} as $id => $pluginDef) {
        $plugin = $manager->createInstance($pluginDef['plugin'], $pluginDef['configuration']);
        // Allows ECA plugins to react upon being added to an ECA entity.
        if ($plugin instanceof PluginUsageInterface) {
          $plugin->pluginUsed($this, $id);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    // As ::trustData() states that dependencies are not calculated on save,
    // calculation is skipped when flagged as trusted.
    // @see Drupal\Core\Config\Entity\ConfigEntityInterface::trustData
    if (static::$isTesting || $this->trustedData) {
      return $this;
    }
    parent::calculateDependencies();
    foreach ($this->dependencyCalculation()->calculateDependencies($this) as $type => $names) {
      foreach ($names as $name) {
        $this->addDependency($type, $name);
      }
    }
    return $this;
  }

  /**
   * Builds the cache ID for an ID inside this ECA config entity.
   *
   * @param string $id
   *   An idea for which a cache ID inside this ECA config entity is needed.
   *
   * @return string
   *   The cache ID.
   */
  protected function buildCacheId(string $id): string {
    return "eca:$this->id:$id";
  }

  /**
   * Determine if the ECA config entity is editable.
   *
   * @return bool
   *   If the associated modeller supports editing inside the Drupal admin UI,
   *   return TRUE, FALSE otherwise.
   */
  public function isEditable(): bool {
    if ($modeller = $this->getModeller()) {
      return $modeller->isEditable();
    }
    return FALSE;
  }

  /**
   * Determine if the ECA config entity is exportable.
   *
   * @return bool
   *   If the associated modeller supports exporting of a model, return TRUE,
   *   FALSE otherwise.
   */
  public function isExportable(): bool {
    if ($modeller = $this->getModeller()) {
      return $modeller->isExportable();
    }
    return FALSE;
  }

  /**
   * Provides the modeller plugin associated with this ECA config entity.
   *
   * @return \Drupal\eca\Plugin\ECA\Modeller\ModellerInterface|null
   *   Returns the modeller plugin if possible, NULL otherwise.
   */
  public function getModeller(): ?ModellerInterface {
    try {
      /** @var \Drupal\eca\Plugin\ECA\Modeller\ModellerInterface $plugin */
      $plugin = $this->modellerPluginManager()->createInstance($this->get('modeller'));
    }
    catch (PluginException $e) {
      $this->logger()->error($e->getMessage());
      return NULL;
    }
    if ($plugin !== NULL) {
      $plugin->setConfigEntity($this);
      return $plugin;
    }
    return NULL;
  }

  /**
   * Provides the ECA model entity storing the data for this ECA config entity.
   *
   * @return \Drupal\eca\Entity\Model
   *   The ECA model entity.
   */
  public function getModel(): Model {
    if (!isset($this->model)) {
      try {
        $storage = $this->entityTypeManager()->getStorage('eca_model');
      }
      catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
        // @todo Log this exception.
        // This should be impossible to ever happen, because this module is
        // providing that storage handler.
        return $this->model;
      }
      /** @var \Drupal\eca\Entity\Model $model */
      $model = $storage->load($this->id());
      if ($model === NULL) {
        $model = $storage->create([
          'id' => $this->id(),
        ]);
      }
      $this->model = $model;
    }
    return $this->model;
  }

  /**
   * Reset all component (events, conditions, actions, gateways) arrays.
   *
   * This should be called by the modeller once before the methods
   * ::addEvent, ::addCondition, ::addAction or ::addGateway will be used.
   */
  public function resetComponents(): void {
    $this->events = [];
    $this->conditions = [];
    $this->actions = [];
    $this->gateways = [];
  }

  /**
   * Add a condition item to this ECA config entity.
   *
   * @param string $id
   *   The condition ID.
   * @param string $plugin_id
   *   The condition's plugin ID.
   * @param array $fields
   *   The configuration for this condition.
   *
   * @return bool
   *   Returns TRUE if the condition's configuration is valid, FALSE otherwise.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   When the given condition plugin ID does not exist.
   */
  public function addCondition(string $id, string $plugin_id, array $fields): bool {
    $plugin = $this->conditionPluginManager()->createInstance($plugin_id, []);
    if (($plugin instanceof PluginFormInterface) && !$this->validatePlugin($plugin, $fields, 'action', $plugin_id, $id)) {
      return FALSE;
    }

    $this->conditions[$id] = [
      'plugin' => $plugin_id,
      'configuration' => $fields,
    ];
    return TRUE;
  }

  /**
   * Add a gateway item to this ECA config entity.
   *
   * @param string $id
   *   The gateway ID.
   * @param int $type
   *   The gateway type.
   * @param array $successors
   *   A list of successor items linked to this gateway.
   *
   * @return bool
   *   Returns TRUE if the gateway was successfully added, FALSE otherwise.
   */
  public function addGateway(string $id, int $type, array $successors): bool {
    $this->gateways[$id] = [
      'type' => $type,
      'successors' => $successors,
    ];
    return TRUE;
  }

  /**
   * Add an event item to this ECA config entity.
   *
   * @param string $id
   *   The event ID.
   * @param string $plugin_id
   *   The event's plugin ID.
   * @param string $label
   *   The event label.
   * @param array $fields
   *   The configuration for this event.
   * @param array $successors
   *   A list of successor items linked to this event.
   *
   * @return bool
   *   Returns TRUE if the event's configuration is valid, FALSE otherwise.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   When the given event plugin ID does not exist.
   */
  public function addEvent(string $id, string $plugin_id, string $label, array $fields, array $successors): bool {
    $plugin = $this->eventPluginManager()->createInstance($plugin_id, []);
    if (($plugin instanceof PluginFormInterface) && !$this->validatePlugin($plugin, $fields, 'action', $plugin_id, $label)) {
      return FALSE;
    }

    if (empty($label)) {
      $label = $id;
    }
    $this->events[$id] = [
      'plugin' => $plugin_id,
      'label' => $label,
      'configuration' => $fields,
      'successors' => $successors,
    ];
    return TRUE;
  }

  /**
   * Add an action item to this ECA config entity.
   *
   * As action plugins are controlled by Drupal core's action plugin manager
   * and not by ECA, this method will run new actions through the configuration
   * form validation and submission and validates, if the given configuration
   * is valid.
   *
   * @param string $id
   *   The action ID.
   * @param string $plugin_id
   *   The action's plugin ID.
   * @param string $label
   *   The action label.
   * @param array $fields
   *   The configuration for this action.
   * @param array $successors
   *   A list of successor items linked to this action.
   *
   * @return bool
   *   Returns TRUE if the action's configuration is valid, FALSE otherwise.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   When the given action plugin ID does not exist.
   */
  public function addAction(string $id, string $plugin_id, string $label, array $fields, array $successors): bool {
    $plugin = $this->actionPluginManager()->createInstance($plugin_id, []);
    if (($plugin instanceof PluginFormInterface) && !$this->validatePlugin($plugin, $fields, 'action', $plugin_id, $label)) {
      return FALSE;
    }

    if (empty($label)) {
      $label = $id;
    }
    $this->actions[$id] = [
      'plugin' => $plugin_id,
      'label' => $label,
      'configuration' => $fields,
      'successors' => $successors,
    ];
    return TRUE;
  }

  /**
   * Validate the configuration of an event, condition or action plugin.
   *
   * @param \Drupal\Core\Plugin\PluginFormInterface $plugin
   *   The plugin to be validated.
   * @param array $fields
   *   The configuration values to be validated.
   * @param string $type
   *   The plugin type, either event, condition or action.
   * @param string $plugin_id
   *   The plugin id.
   * @param string $label
   *   The label in the model.
   *
   * @return bool
   *   TRUE, if configuration form validation has no errors. FALSE otherwise.
   */
  protected function validatePlugin(PluginFormInterface $plugin, array &$fields, string $type, string $plugin_id, string $label): bool {
    if ($plugin instanceof ConfigurableInterface) {
      // Convert potential strings from pseudo-checkboxes back to boolean.
      foreach ($plugin->defaultConfiguration() as $key => $value) {
        if (is_bool($value) && isset($fields[$key]) && !is_bool($fields[$key])) {
          $fields[$key] = mb_strtolower($fields[$key]) === 'yes';
        }
      }
    }
    // Build form.
    $form_state = new FormState();
    $form = $plugin->buildConfigurationForm([], $form_state);
    // Set form field values, simulating the user filling and submitting
    // the form.
    $form_state->setValues($fields);
    if (!in_array($plugin_id, self::$ignoreConfigValidationActions, TRUE)) {
      // Validate the form.
      $plugin->validateConfigurationForm($form, $form_state);
    }
    // Check for errors.
    if ($errors = $form_state->getErrors()) {
      foreach ($errors as $error) {
        $errorMsg = sprintf('%s "%s" (%s): %s', $type, $plugin->getPluginDefinition()['label'], $label, $error);
        $this->messenger()->addError($errorMsg);
      }
      return FALSE;
    }
    // Simulate submitting the form.
    $plugin->submitConfigurationForm($form, $form_state);
    // Collect the resulting form field values.
    $fields = $plugin->getConfiguration() + $fields;
    return TRUE;
  }

  /**
   * Returns a list of info strings about included events in this ECA model.
   *
   * @return array
   *   A list of info strings about included events in this ECA model.
   */
  public function getEventInfos(): array {
    $events = [];
    foreach ($this->getUsedEvents() as $used_event) {
      $plugin = $used_event->getPlugin();
      $event_info = $plugin->getPluginDefinition()['label'];
      // If available, additionally display the first config value of the event.
      if ($event_config = $used_event->getConfiguration()) {
        $first_key = key($event_config);
        $first_value = current($event_config);
        $form = $plugin->buildConfigurationForm([], new FormState());
        if (isset($form[$first_key]['#options'][$first_value])) {
          $first_value = $form[$first_key]['#options'][$first_value];
        }
        $event_info .= ' (' . $first_value . ')';
      }
      $events[] = $event_info;
    }
    return $events;
  }

  /**
   * Provides a list of used events by this ECA config entity.
   *
   * @return \Drupal\eca\Entity\Objects\EcaEvent[]
   *   The list of used events.
   */
  public function getUsedEvents(): array {
    if ($cached = $this->memoryCache()->get($this->buildCacheId('events'))) {
      return $cached->data;
    }

    $events = [];
    foreach ($this->events as $id => $def) {
      if ($event = $this->getEcaObject('event', $def['plugin'], $id, $def['label'] ?? 'noname', $def['configuration'] ?? [], $def['successors'] ?? [])) {
        $events[$id] = $event;
      }
    }
    $this->memoryCache()->set($this->buildCacheId('events'), $events, CacheBackendInterface::CACHE_PERMANENT, ['eca.memory_cache:' . $this->id]);
    return $events;
  }

  /**
   * Provides a list of valid successors to any ECA item in a given context.
   *
   * @param \Drupal\eca\Entity\Objects\EcaObject $eca_object
   *   The ECA item, for which the successors are requested.
   * @param \Drupal\Component\EventDispatcher\Event|\Symfony\Contracts\EventDispatcher\Event $event
   *   The originally triggered event in which context to determine the list
   *   of valid successors.
   * @param array $context
   *   A list of tokens from the current context to be used for meaningful
   *   log messages.
   *
   * @return \Drupal\eca\Entity\Objects\EcaObject[]
   *   The list of valid successors.
   */
  public function getSuccessors(EcaObject $eca_object, object $event, array $context): array {
    $successors = [];
    foreach ($eca_object->getSuccessors() as $successor) {
      $context['%successorid'] = $successor['id'];
      if ($action = $this->actions[$successor['id']] ?? FALSE) {
        $context['%successorlabel'] = $action['label'] ?? 'noname';
        $this->logger()->debug('Check action successor %successorlabel (%successorid) from ECA %ecalabel (%ecaid) for event %event.', $context);
        if ($successorObject = $this->getEcaObject('action', $action['plugin'], $successor['id'], $action['label'] ?? 'noname', $action['configuration'] ?? [], $action['successors'] ?? [], $eca_object->getEvent())) {
          if ($this->conditionServices()->assertCondition($event, $successor['condition'], $this->conditions[$successor['condition']] ?? NULL, $context)) {
            $successors[] = $successorObject;
          }
        }
        else {
          $this->logger()->error('Invalid action successor %successorlabel (%successorid) from ECA %ecalabel (%ecaid) for event %event.', $context);
        }
      }
      elseif ($gateway = $this->gateways[$successor['id']] ?? FALSE) {
        $context['%successorlabel'] = $gateway['label'] ?? 'noname';
        $this->logger()->debug('Check gateway successor %successorlabel (%successorid) from ECA %ecalabel (%ecaid) for event %event.', $context);
        $successorObject = new EcaGateway($this, $successor['id'], $gateway['label'] ?? 'noname', $eca_object->getEvent(), $gateway['type']);
        $successorObject->setSuccessors($gateway['successors']);
        if ($this->conditionServices()->assertCondition($event, $successor['condition'], $this->conditions[$successor['condition']] ?? NULL, $context)) {
          $successors[] = $successorObject;
        }
      }
      else {
        $this->logger()->error('Non existent successor (%successorid) from ECA %ecalabel (%ecaid) for event %event.', $context);
      }
    }
    return $successors;
  }

  /**
   * Provides an ECA item build from given properties.
   *
   * @param string $type
   *   The ECA object type. Can bei either "event" or "action".
   * @param string $plugin_id
   *   The plugin ID.
   * @param string $id
   *   The item ID given by the modeller.
   * @param string $label
   *   The label.
   * @param array $fields
   *   The configuration of the item.
   * @param array $successors
   *   The list of associated successors.
   * @param \Drupal\eca\Entity\Objects\EcaEvent|null $event
   *   The original ECA event object, if looking for an action, NULL otherwise.
   *
   * @return \Drupal\eca\Entity\Objects\EcaObject|null
   *   The ECA object if available, NULL otherwise.
   */
  private function getEcaObject(string $type, string $plugin_id, string $id, string $label, array $fields, array $successors, EcaEvent $event = NULL): ?EcaObject {
    $ecaObject = NULL;
    switch ($type) {
      case 'event':
        try {
          /** @var \Drupal\eca\Plugin\ECA\Event\EventInterface $plugin */
          $plugin = $this->eventPluginManager()->createInstance($plugin_id, $fields);
        }
        catch (PluginException $e) {
          // This can be ignored.
        }
        if (isset($plugin)) {
          $ecaObject = new EcaEvent($this, $id, $label, $plugin);
        }
        break;

      case 'action':
        if ($event !== NULL) {
          try {
            /** @var \Drupal\Core\Action\ActionInterface $plugin */
            $plugin = $this->actionPluginManager()->createInstance($plugin_id, $fields);
          }
          catch (PluginException $e) {
            // This can be ignored.
          }
          if (isset($plugin)) {
            $ecaObject = new EcaAction($this, $id, $label, $event, $plugin);
          }
        }
        break;

    }
    if ($ecaObject !== NULL) {
      foreach ($fields as $key => $value) {
        $ecaObject->setConfiguration($key, $value);
      }
      $ecaObject->setSuccessors($successors);
      return $ecaObject;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections(): array {
    $collections = [];
    if (!empty($this->events)) {
      foreach ($this->events as $id => $info) {
        if (empty($info['plugin'])) {
          continue;
        }
        $collections['events.' . $id] = new DefaultSingleLazyPluginCollection($this->eventPluginManager(), $info['plugin'], $info['configuration'] ?? []);
      }
    }
    if (!empty($this->conditions)) {
      foreach ($this->conditions as $id => $info) {
        if (empty($info['plugin'])) {
          continue;
        }
        $collections['conditions.' . $id] = new DefaultSingleLazyPluginCollection($this->conditionPluginManager(), $info['plugin'], $info['configuration'] ?? []);
      }
    }
    if (!empty($this->actions)) {
      foreach ($this->actions as $id => $info) {
        if (empty($info['plugin'])) {
          continue;
        }
        $collections['actions.' . $id] = new DefaultSingleLazyPluginCollection($this->actionPluginManager(), $info['plugin'], $info['configuration'] ?? []);
      }
    }
    return $collections;
  }

  /**
   * Adds a dependency that could only be calculated on runtime.
   *
   * After adding a dependency on runtime, this configuration should be saved.
   *
   * @param string $type
   *   Type of dependency being added: 'module', 'theme', 'config', 'content'.
   * @param string $name
   *   If $type is 'module' or 'theme', the name of the module or theme. If
   *   $type is 'config' or 'content', the result of
   *   EntityInterface::getConfigDependencyName().
   *
   * @see \Drupal\Core\Entity\EntityInterface::getConfigDependencyName()
   *
   * @return static
   *   The ECA config itself.
   */
  public function addRuntimeDependency(string $type, string $name): Eca {
    $this->addDependency($type, $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    $this->memoryCache()->invalidateAll();
    $storage->resetCache();
    parent::postSave($storage, $update);
  }

}
