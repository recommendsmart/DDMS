<?php

namespace Drupal\eca\Plugin\Action;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\EventDispatcher\Event;
use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ActionBase as CoreActionBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\eca\EcaState;
use Drupal\eca\Token\TokenInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for ECA provided actions.
 */
abstract class ActionBase extends CoreActionBase implements ContainerFactoryPluginInterface, ActionInterface {

  /**
   * @var \Drupal\Component\EventDispatcher\Event
   */
  protected Event $event;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The ECA-related token services.
   *
   * @var \Drupal\eca\Token\TokenInterface
   */
  protected TokenInterface $tokenServices;

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected TimeInterface $time;

  /**
   * @var \Drupal\eca\EcaState
   */
  protected EcaState $state;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, TokenInterface $token_services, AccountProxyInterface $current_user, TimeInterface $time, EcaState $state) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->tokenServices = $token_services;
    $this->currentUser = $current_user;
    $this->time = $time;
    $this->state = $state;

    if ($this instanceof ConfigurableInterface) {
      $this->setConfiguration($configuration);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): ActionBase {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('eca.token_services'),
      $container->get('current_user'),
      $container->get('datetime.time'),
      $container->get('eca.state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setEvent(Event $event): ActionInterface {
    $this->event = $event;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEvent(): Event {
    return $this->event;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = AccessResult::allowed();
    return $return_as_object ? $result : $result->isAllowed();
  }

}
