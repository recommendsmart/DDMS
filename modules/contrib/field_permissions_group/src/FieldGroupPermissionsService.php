<?php

namespace Drupal\field_permissions_group;

use Drupal\field_permissions\FieldPermissionsService;
use Drupal\field_permissions\Plugin\FieldPermissionTypeInterface;
use Drupal\field_permissions\Plugin\CustomPermissionsInterface;

/**
 * The field permission service for group roles and permissions.
 */
class FieldGroupPermissionsService extends FieldPermissionsService {

  /**
   * {@inheritdoc}
   */
  public function getGroupPermissionsByRole() {
    /** @var \Drupal\group\Entity\GroupRoleInterface[] $roles */
    $roles = $this->entityTypeManager->getStorage('group_role')->loadMultiple();
    $field_field_permissions = [];
    $field_permission_perm = $this->getAllGroupPermissions();
    foreach ($roles as $role_name => $role) {
      $role_permissions = $role->getPermissions();
      $field_field_permissions[$role_name] = [];
      foreach ($role_permissions as $key => $role_permission) {
        if (in_array($role_permission, array_keys($field_permission_perm))) {
          $field_field_permissions[$role_name][] = $role_permission;
        }
      }
    }
    return $field_field_permissions;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllGroupPermissions() {
    $permissions = [];
    /** @var \Drupal\field\FieldConfigInterface[] $fields */
    $fields = $this->entityTypeManager->getStorage('field_config')->loadMultiple();
    foreach ($fields as $key => $field) {
      // Check if this plugin defines custom permissions.
      $permission_type = $this->fieldGetPermissionType($field);
      if ($permission_type !== FieldPermissionTypeInterface::ACCESS_PUBLIC) {
        $plugin = $this->permissionTypeManager->createInstance($permission_type, [], $field);
        if ($plugin instanceof CustomPermissionsInterface) {
          $permissions += $plugin->getPermissions();
        }
      }
    }
    return $permissions;
  }

}
