<?php

namespace Drupal\user_manual;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\BundlePermissionHandlerTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user_manual\Entity\UserManual;
use Drupal\user_manual\Entity\UserManualType;
use Drupal\user_manual\Entity\UserManualTypeEntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic permissions of the user_manual module.
 *
 * @see user_manual.permissions.yml
 */
class UserManualPermissions implements ContainerInjectionInterface {
  use BundlePermissionHandlerTrait;
  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a UserManualPermissions instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'));
  }

  /**
   * Get user_manual permissions.
   *
   * @return array
   *   Permissions array.
   */
  public function permissions() {
    return $this->generatePermissions(UserManualType::loadMultiple(), [$this, 'buildPermissions']);
  }

  /**
   * Builds a standard list of user_manual permissions for a given types.
   *
   * @param \Drupal\user_manual\UserManualTypeEntityInterface $user_manual_type
   *   The user manual type.
   *
   * @return array
   *   An array of permission names and descriptions.
   */
  protected function buildPermissions(UserManualTypeEntityInterface $user_manual_type) {
    $id = $user_manual_type->id();
    $args = ['%umt' => $user_manual_type->label()];

    return [
      "create user_manual in $id" => ['title' => $this->t('%umt: Create user manual', $args)],
      "delete user_manual in $id" => ['title' => $this->t('%umt: Delete user manual', $args)],
      "edit user_manual in $id" => ['title' => $this->t('%umt: Edit user manual', $args)],
    ];
  }

}
