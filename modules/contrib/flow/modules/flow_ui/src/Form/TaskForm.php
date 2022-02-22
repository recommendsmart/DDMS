<?php

namespace Drupal\flow_ui\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\flow\Entity\Flow;
use Drupal\flow\Entity\FlowInterface;
use Drupal\flow\FlowCompatibility;
use Drupal\flow\FlowTaskMode;
use Drupal\flow\Plugin\FlowSubjectInterface;
use Drupal\flow\Plugin\FlowTaskInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for configuring a Flow task plugin.
 */
class TaskForm implements FormInterface, ContainerInjectionInterface {

  use DependencySerializationTrait;
  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected MessengerInterface $messenger;

  /**
   * The Flow config entity.
   *
   * @var \Drupal\flow\Entity\FlowInterface|null
   */
  protected ?FlowInterface $flow;

  /**
   * The Flow task plugin.
   *
   * @var \Drupal\flow\Plugin\FlowTaskInterface|null
   */
  protected ?FlowTaskInterface $task;

  /**
   * The Flow subject plugin.
   *
   * @var \Drupal\flow\Plugin\FlowSubjectInterface|null
   */
  protected ?FlowSubjectInterface $subject;

  /**
   * The position of the Flow task within the tasks list of the Flow config.
   *
   * @var int|null
   */
  protected ?int $taskIndex;

  /**
   * The target entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected EntityTypeInterface $targetEntityType;

  /**
   * The TaskForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\flow\Entity\FlowInterface|null $flow
   *   The Flow config entity.
   * @param \Drupal\flow\Plugin\FlowTaskInterface|null $task
   *   The Flow task plugin.
   * @param \Drupal\flow\Plugin\FlowSubjectInterface|null $subject
   *   The Flow subject plugin.
   * @param int|null $task_index
   *   The position of the Flow task within the tasks list of the Flow config.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MessengerInterface $messenger, ?FlowInterface $flow = NULL, ?FlowTaskInterface $task = NULL, ?FlowSubjectInterface $subject = NULL, ?int $task_index = NULL) {
    $this->entityTypeManager = $entity_type_manager;
    $this->messenger = $messenger;
    $this->flow = $flow;
    $this->task = $task;
    $this->subject = $subject;
    $this->taskIndex = $task_index;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, ?FlowInterface $flow = NULL, ?FlowTaskInterface $task = NULL, ?FlowSubjectInterface $subject = NULL, ?int $task_index = NULL) {
    $instance = new static($container->get('entity_type.manager'), $container->get('messenger'), $flow, $task, $subject, $task_index);
    $instance->setStringTranslation($container->get('string_translation'));
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'flow_task_plugin';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?FlowInterface $flow = NULL, ?FlowTaskInterface $task = NULL, ?FlowSubjectInterface $subject = NULL, ?int $task_index = NULL) {
    $form['#tree'] = TRUE;
    $form['#process'][] = '::processForm';
    $form['#after_build'][] = '::afterBuild';
    $this->initProperties($form, $form_state, $flow, $task, $subject, $task_index);
    $task = $this->task;
    $subject = $this->subject;
    $task_definition = $task->getPluginDefinition();
    $task_config = $task->getConfiguration();

    $weight = 0;
    $task_is_new = !($this->taskIndex < $this->flow->getTasks()->count());

    $weight += 10;
    $subject_definition = $this->subject->getPluginDefinition();
    $form['subject'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('@type: @name', [
        '@type' => $this->t('Subject'),
        '@name' => $subject_definition['label'],
      ]),
      '#weight' => $weight++,
    ];
    if ($subject instanceof PluginFormInterface) {
      $form['subject']['settings'] = [];
      $subject_form_state = SubformState::createForSubform($form['subject']['settings'], $form, $form_state);
      $form['subject']['settings'] = $subject->buildConfigurationForm($form['subject']['settings'], $subject_form_state);
    }
    else {
      $form['subject']['no_settings'] = [
        '#type' => 'markup',
        '#markup' => $this->t('This subject does not provide any settings.'),
      ];
    }

    $form['task'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('@type: @name', [
        '@type' => $this->t('Task'),
        '@name' => $task_definition['label'],
      ]),
      '#weight' => $weight++,
    ];
    if ($task instanceof PluginFormInterface) {
      $form['task']['settings'] = [];
      $task_form_state = SubformState::createForSubform($form['task']['settings'], $form, $form_state);
      $form['task']['settings'] = $task->buildConfigurationForm($form['task']['settings'], $task_form_state);
    }
    else {
      $form['task']['no_settings'] = [
        '#type' => 'markup',
        '#markup' => $this->t('This task does not provide any settings.'),
      ];
    }

    $weight += 10;
    $form['execution'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Task execution'),
      '#weight' => $weight++,
    ];
    $execution_options = [
      'now' => $this->t('Immediately on @mode', ['@mode' => $this->flow->getTaskMode()]),
      'after' => $this->t('Immediately after @mode', ['@mode' => $this->flow->getTaskMode()]),
      'queue' => $this->t('Enqueue for running in the background'),
    ];
    $form['execution']['start'] = [
      '#type' => 'select',
      '#title' => $this->t('Start method'),
      '#title_display' => 'invisible',
      '#description' => $this->t('Different values of information may be available during and after the @mode operation. For example, when a new @type item is being created, the @type ID is only available after it got saved.', [
        '@type' => $this->entityTypeManager->getDefinition($this->flow->getTargetEntityTypeId())->getLabel(),
        '@mode' => $this->flow->getTaskMode(),
      ]),
      '#options' => $execution_options,
      '#default_value' => $task_config['execution']['start'] ?? 'now',
      '#required' => TRUE,
      '#weight' => 10,
    ];

    $weight += 100;
    $form['actions']['#weight'] = $weight++;
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#submit' => ['::submitForm', '::save', '::redirectAfterSave'],
      '#weight' => 10,
    ];
    if (!$task_is_new && isset($task_config['active']) && !$task_config['active']) {
      $form['actions']['delete'] = [
        '#type' => 'submit',
        '#value' => $this->t('Delete'),
        '#access' => $this->flow->access('delete'),
        '#submit' => ['::delete'],
        '#attributes' => [
          'class' => ['button', 'button--danger'],
        ],
        '#button_type' => 'danger',
        '#weight' => 20,
      ];
    }

    $form['config'] = ['#tree' => TRUE, '#weight' => $weight++];
    $form['config']['entity_type'] = [
      '#type' => 'hidden',
      '#value' => $this->flow->getTargetEntityTypeId(),
    ];
    $form['config']['bundle'] = [
      '#type' => 'hidden',
      '#value' => $this->flow->getTargetBundle(),
    ];
    $form['config']['task_mode'] = [
      '#type' => 'hidden',
      '#value' => $this->flow->getTaskMode(),
    ];
    $form['config']['task_plugin_id'] = [
      '#type' => 'hidden',
      '#value' => $this->task->getPluginId(),
    ];
    $form['config']['subject_plugin_id'] = [
      '#type' => 'hidden',
      '#value' => $this->subject->getPluginId(),
    ];
    $form['config']['task_index'] = [
      '#type' => 'hidden',
      '#value' => $this->taskIndex,
    ];

    return $form;
  }

  /**
   * Process callback.
   */
  public function processForm($element, FormStateInterface $form_state, $form) {
    $this->flow = $form_state->get('flow');
    $this->task = $form_state->get('task');
    $this->subject = $form_state->get('subject');
    $this->taskIndex = $form_state->get('task_index');
    return $element;
  }

  /**
   * After build callback.
   */
  public function afterBuild(array $element, FormStateInterface $form_state) {
    if ($form_state->hasValue(['task', 'settings'])) {
      $values = $form_state->getValue(['task', 'settings']);
      array_walk_recursive($values, function (&$value) {
        if ($value === '_none') {
          $value = NULL;
        }
      });
      $this->task->setSettings($values);
    }
    if ($form_state->hasValue(['subject', 'settings'])) {
      $values = $form_state->getValue(['subject', 'settings']);
      array_walk_recursive($values, function (&$value) {
        if ($value === '_none') {
          $value = NULL;
        }
      });
      $this->subject->setSettings($values);
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!$this->flow->access('update')) {
      $form_state->setError($form, $this->t('You don\'t have permission to manage this configuration.'));
    }

    if ($triggering_element = &$form_state->getTriggeringElement()) {
      if (isset($triggering_element['#parents']) && reset($triggering_element['#parents']) !== 'actions') {
        return;
      }
    }

    $subject = $this->subject;
    $task = $this->task;
    if ($subject instanceof PluginFormInterface) {
      $subject_form_state = SubformState::createForSubform($form['subject']['settings'], $form, $form_state);
      $subject->validateConfigurationForm($form['subject']['settings'], $subject_form_state);
    }
    if ($task instanceof PluginFormInterface) {
      $task_form_state = SubformState::createForSubform($form['task']['settings'], $form, $form_state);
      $task->validateConfigurationForm($form['task']['settings'], $task_form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (!$this->flow->access('update')) {
      return;
    }
    if ($triggering_element = &$form_state->getTriggeringElement()) {
      if (isset($triggering_element['#parents']) && reset($triggering_element['#parents']) !== 'actions') {
        return;
      }
    }

    $subject = $this->subject;
    $task = $this->task;
    if (isset($form['subject']['settings']) && $subject instanceof PluginFormInterface) {
      $subject_form_state = SubformState::createForSubform($form['subject']['settings'], $form, $form_state);
      $subject->submitConfigurationForm($form['subject']['settings'], $subject_form_state);
    }
    if (isset($form['task']['settings']) && $task instanceof PluginFormInterface) {
      $task_form_state = SubformState::createForSubform($form['task']['settings'], $form, $form_state);
      $task->submitConfigurationForm($form['task']['settings'], $task_form_state);
    }
  }

  /**
   * Redirect after save submission callback.
   *
   * @param array &$form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function redirectAfterSave(array &$form, FormStateInterface $form_state) {
    if (!$this->flow->access('update')) {
      return;
    }

    $config = $this->flow;
    $task_modes = FlowTaskMode::service()->getAvailableTaskModes();

    $t_args = [
      '%task_mode' => $task_modes[$config->getTaskMode()],
      '%type' => $this->entityTypeManager->getDefinition($config->getTargetEntityTypeId())->getLabel(),
    ];
    $message = $this->t('The %task_mode flow configuration for %type has been saved.', $t_args);

    $this->messenger->addStatus($message);

    $bundle_type_id = $this->targetEntityType->getBundleEntityType() ?: 'bundle';
    $form_state->setRedirect("entity.flow.{$this->targetEntityType->id()}.task_mode", [
      'entity_type_id' => $this->targetEntityType->id(),
      $bundle_type_id => $this->flow->getTargetBundle(),
      'flow_task_mode' => $this->flow->getTaskMode(),
    ]);
  }

  /**
   * Save submission callback.
   *
   * @param array &$form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function save(array &$form, FormStateInterface $form_state): void {
    if (!$this->flow->access('update')) {
      return;
    }
    $flow = $this->flow;
    $task = $this->task;
    $subject = $this->subject;
    $tasks_array = $flow->get('tasks');
    $task_is_new = !($this->taskIndex < $this->flow->getTasks()->count());
    $tasks_array[$this->taskIndex] = [
      'id' => $task->getPluginId(),
      'type' => $task->getBaseId(),
      'weight' => $this->taskIndex,
      'active' => !$task_is_new && !empty($task->getConfiguration()['active']),
      'execution' => $form_state->getValue('execution', ['start' => 'now']),
      'subject' => [
        'id' => $subject->getPluginId(),
        'type' => $subject->getBaseId(),
        'settings' => $subject->getSettings(),
        'third_party_settings' => [],
      ],
      'settings' => $task->getSettings(),
      'third_party_settings' => [],
    ];
    foreach ($task->getThirdPartyProviders() as $provider) {
      $tasks_array[$this->taskIndex]['third_party_settings'][$provider] = $task->getThirdPartySettings($provider);
    }
    foreach ($subject->getThirdPartyProviders() as $provider) {
      $tasks_array[$this->taskIndex]['subject']['third_party_settings'][$provider] = $subject->getThirdPartySettings($provider);
    }
    $flow->setTasks($tasks_array);
    $flow->save();
  }

  /**
   * Delete submission callback that redirects to the task delete form.
   *
   * @param array &$form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function delete(array &$form, FormStateInterface $form_state): void {
    $flow = $this->flow;
    $target_type = $this->targetEntityType;
    $bundle_type_id = $target_type->getBundleEntityType() ?: 'bundle';
    $form_state->setRedirect("flow.task.{$target_type->id()}.delete", [
      'entity_type_id' => $target_type->id(),
      $bundle_type_id => $flow->getTargetBundle(),
      'flow_task_mode' => $flow->getTaskMode(),
      'flow_task_index' => $this->taskIndex,
    ]);
  }

  /**
   * Initializes the form object properties.
   *
   * @param array &$form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param \Drupal\flow\Entity\FlowInterface|null $flow
   *   The Flow config entity.
   * @param \Drupal\flow\Plugin\FlowTaskInterface|null $task
   *   The Flow task plugin.
   * @param \Drupal\flow\Plugin\FlowSubjectInterface|null $subject
   *   The Flow subject plugin.
   * @param int|null $task_index
   *   The position of the Flow task within the tasks list of the Flow config.
   */
  protected function initProperties(array &$form, FormStateInterface $form_state, ?FlowInterface $flow = NULL, ?FlowTaskInterface $task = NULL, ?FlowSubjectInterface $subject = NULL, ?int $task_index = NULL): void {
    if ($form_state->has('flow')) {
      $this->flow = $form_state->get('flow');
      $this->task = $form_state->get('task');
      $this->subject = $form_state->get('subject');
      $this->taskIndex = $form_state->get('task_index');
    }
    elseif (isset($flow, $task, $subject, $task_index)) {
      $this->flow = $flow;
      $this->task = $task;
      $this->subject = $subject;
      $this->taskIndex = $task_index;
    }
    elseif ($config_values = $form_state->getValue('config')) {
      $config_values = $form_state->getValue('config');
      $this->flow = Flow::getFlow($config_values['entity_type'], $config_values['bundle'], $config_values['task_mode']);
      $tasks = $this->flow->getTasks();
      $subjects = $this->flow->getSubjects();
      if ($tasks->has($config_values['task_index'])) {
        $this->task = $tasks->get($config_values['task_index']);
        $this->subject = $subjects->get($config_values['task_index']);
      }
      else {
        $flow_keys = [
          'entity_type_id' => $this->flow->getTargetEntityTypeId(),
          'bundle' => $this->flow->getTargetBundle(),
          'task_mode' => $this->flow->getTaskMode(),
        ];
        /** @var \Drupal\flow\Plugin\FlowTaskManager $task_manager */
        $task_manager = \Drupal::service('plugin.manager.flow.task');
        $this->task = $task_manager->createInstance($config_values['task_plugin_id'], $flow_keys);
        /** @var \Drupal\flow\Plugin\FlowSubjectManager $subject_manager */
        $subject_manager = \Drupal::service('plugin.manager.flow.subject');
        $this->subject = $subject_manager->createInstance($config_values['subject_plugin_id'], $flow_keys);
      }
      $this->taskIndex = $config_values['task_index'];
    }
    else {
      throw new \InvalidArgumentException("Form build error: The Flow task plugin form cannot be built without any information about according configuration.");
    }
    if (!FlowCompatibility::validate($this->flow, $this->task, $this->subject)) {
      throw new \InvalidArgumentException('Form build error: The Flow task form cannot not be built with incompatible components.');
    }
    $this->targetEntityType = $this->entityTypeManager->getDefinition($this->flow->getTargetEntityTypeId());
    $form_state->set('flow', $this->flow);
    $form_state->set('task', $this->task);
    $form_state->set('subject', $this->subject);
    $form_state->set('task_index', $this->taskIndex);
  }

}
