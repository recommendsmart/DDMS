<?php

namespace Drupal\Tests\user_manual\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use Drupal\user\RoleInterface;
use Drupal\user_manual\Entity\UserManual;
use Drupal\user_manual\Entity\UserManualType;
use Drupal\user_manual\UserManualAccessControlHandler;

/**
 * Test basic functionality of User Manual module.
 *
 * @group user_manual
 */
class AccessTest extends BrowserTestBase {

  use StringTranslationTrait;

  /**
   * User with no permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $noManualUser;

  /**
   * User without permission to view user manual.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $manualViewUser;

  /**
   * User without permission to perform CRUD operations on own user manual.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $manualCrudOwnUser;

  /**
   * User without permission to perform CRUD operations on all user manuals.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $manualCrudAnyUser;

  /**
   * User with permission to view and edit revisions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $manualReviseAnyUser;


  /**
   * Default theme.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'user',
    'text',
    'field',
    'taxonomy',
    'views',
    'entity',
    'user_manual',
    'user_manual_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() :void {
    parent::setUp();

    $this->noManualUser = $this->drupalCreateUser([]);

    $this->manualViewUser = $this->drupalCreateUser([
      UserManualAccessControlHandler::VIEW_USER_MANUAL_PERMISSION,
    ]);

    $this->manualCrudOwnUser = $this->drupalCreateUser([
      UserManualAccessControlHandler::CREATE_USER_MANUAL_PERMISSION,
      UserManualAccessControlHandler::EDIT_OWN_USER_MANUAL_PERMISSION,
      UserManualAccessControlHandler::DELETE_OWN_USER_MANUAL_PERMISSION,
    ]);

    $this->manualCrudAnyUser = $this->drupalCreateUser([
      UserManualAccessControlHandler::CREATE_USER_MANUAL_PERMISSION,
      UserManualAccessControlHandler::EDIT_ANY_USER_MANUAL_PERMISSION,
      UserManualAccessControlHandler::DELETE_ANY_USER_MANUAL_PERMISSION,
    ]);

    $this->manualReviseAnyUser = $this->drupalCreateUser([
      UserManualAccessControlHandler::VIEW_USER_MANUAL_PERMISSION,
      UserManualAccessControlHandler::EDIT_ANY_USER_MANUAL_PERMISSION,
      'view all user_manual revisions',
      'revert all user_manual revisions',
    ]);

  }

  /**
   * Users with no manual related permissions can not access user manual.
   */
  public function testNoManualAccess() {
    // User with no permission can not access manual page.
    $this->drupalLogin($this->noManualUser);
    $this->drupalGet('/admin/user-manual');
    $this->assertSession()->statusCodeEquals(403);

    // User with no permission can not create user manual type.
    $this->drupalGet('/admin/structure/user_manual_type/add');
    $this->assertSession()->statusCodeEquals(403);

    // User with no permission can not edit user manual type.
    $user_manual_type = $this->createUserManualType();
    $this->drupalGet('/admin/structure/user_manual_type/' . $user_manual_type->id() . '/edit');
    $this->assertSession()->statusCodeEquals(403);

    // User with no permission can not access manual page.
    $this->drupalGet('/admin/user-manual/add');
    $this->assertSession()->statusCodeEquals(403);

    // User with no permission can not edit user manuals.
    $user_manual = $this->createUserManual(['bundle' => $user_manual_type->id()]);
    $this->drupalGet('/admin/user-manual/' . $user_manual->id() . '/edit');
    $this->assertSession()->statusCodeEquals(403);

    // Test 'add user_manual in BUNDLE' permission.
    $this->drupalGet('/admin/user-manual/add/' . $user_manual->bundle());
    $this->assertSession()->statusCodeEquals(403);
    $permissions = ['create user_manual in ' . $user_manual->bundle()];
    $role = Role::load(RoleInterface::AUTHENTICATED_ID);
    $this->grantPermissions($role, $permissions);
    $this->drupalGet('/admin/user-manual/add/' . $user_manual->bundle());
    $this->assertSession()->statusCodeEquals(200);

    // Test 'edit user_manual in BUNDLE' and 'delete user_manual in BUNDLE' permissions.
    $this->drupalGet('/admin/user-manual/' . $user_manual->id() . '/edit');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('/admin/user-manual/' . $user_manual->id() . '/delete');
    $this->assertSession()->statusCodeEquals(403);
    $permissions = [
      'edit user_manual in ' . $user_manual->bundle(),
      'delete user_manual in ' . $user_manual->bundle(),
    ];
    $role = Role::load(RoleInterface::AUTHENTICATED_ID);
    $this->grantPermissions($role, $permissions);
    $this->drupalGet('/admin/user-manual/' . $user_manual->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('/admin/user-manual/' . $user_manual->id() . '/delete');
    $this->assertSession()->statusCodeEquals(200);
    user_role_revoke_permissions($role->id(), $permissions);
  }


  /**
   * Users with crud own permissions can only perform crud on their own manuals.
   */
  public function testCreateEditOwnAccess() {
    $this->drupalLogin($this->manualCrudOwnUser);

    // User with `create user_manual` can access the add page.
    $this->drupalGet('/admin/user-manual/add');
    $this->assertSession()->statusCodeEquals(200);

    // User with `edit own user_manual` access can access the edit page
    // for their own user_manuals.
    $user_manual_type = $this->createUserManualType();
    $values = [
      'bundle' => $user_manual_type->id(),
      'user_id' => $this->manualCrudOwnUser->id()
    ];
    $manual = $this->createUserManual($values);
    $this->drupalGet('/admin/user-manual/' . $manual->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);

    // User with `edit own user_manual` access can access the delete page
    // for their own user_manuals.
    $this->drupalGet('/admin/user-manual/' . $manual->id() . '/delete');
    $this->assertSession()->statusCodeEquals(200);

    // User with only `edit own user_manual` access can not access edit page
    // for user manuals created by others.
    $values['user_id'] = 0;
    $manual2 = $this->createUserManual($values);
    $this->drupalGet('/admin/user-manual/' . $manual2->id() . '/edit');
    $this->assertSession()->statusCodeEquals(403);

    // User with only `edit own user_manual` access can not access delete page
    // for user manuals created by others.
    $this->drupalGet('/admin/user-manual/' . $manual2->id() . '/delete');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Users with crud any permissions can perform crud operations on any manuals.
   */
  public function testCreateEditAnyAccess() {
    $this->drupalLogin($this->manualCrudAnyUser);

    // User with `create user_manual` can access the add page.
    $this->drupalGet('/admin/user-manual/add');
    $this->assertSession()->statusCodeEquals(200);

    // User with `edit own user_manual` access can access the edit page
    // for their own user_manuals.
    $user_manual_type = $this->createUserManualType();
    $values = [
      'bundle' => $user_manual_type->id(),
      'user_id' => $this->manualCrudOwnUser->id()
    ];
    $manual = $this->createUserManual($values);
    $this->drupalGet('/admin/user-manual/' . $manual->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);

    // User with `edit own user_manual` access can access the delete page
    // for their own user_manuals.
    $this->drupalGet('/admin/user-manual/' . $manual->id() . '/delete');
    $this->assertSession()->statusCodeEquals(200);

    // User with only `edit own user_manual` access can access edit page
    // for user manuals created by others.
    $values['user_id'] = 0;
    $manual2 = $this->createUserManual($values);
    $this->drupalGet('/admin/user-manual/' . $manual2->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);

    // User with only `edit own user_manual` access can access delete page
    // for user manuals created by others.
    $this->drupalGet('/admin/user-manual/' . $manual2->id() . '/delete');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Only users with revision permissions can view and revert revisions.
   */
  public function testRevisionAccess() {
    $this->drupalLogin($this->manualReviseAnyUser);

    $user_manual_type = $this->createUserManualType();
    $values = [
      'bundle' => $user_manual_type->id(),
      'user_id' => 0
    ];
    $manual = $this->createUserManual($values);
    $original_vid = $manual->getRevisionId();
    $this->drupalGet('/admin/user-manual/' . $manual->id() . '/revisions');

    $this->assertSession()->statusCodeEquals(200);
    $this->createNewManualRevision($manual);

    $this->drupalGet('/admin/user-manual/' . $manual->id() . '/revisions/' . $original_vid . '/revert');
    $this->assertSession()->statusCodeEquals(200);

    // User without revision permissions can not access revisions or
    // revert them.
    $this->drupalLogin($this->manualCrudAnyUser);
    $this->drupalGet('/admin/user-manual/' . $manual->id() . '/revisions');
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalGet('/admin/user-manual/' . $manual->id() . '/revisions/' . $original_vid . '/revert');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Helper function to create new revision to user manual.
   */
  private function createNewManualRevision(UserManual $manual) {
    $manual->set('name', $this->randomMachineName(8));
    $manual->setNewRevision();
    $manual->setRevisionLogMessage($this->randomMachineName(32));
    $manual->save();
    return $manual;
  }

  /**
   * Helper function to create user manual type.
   */
  private function createUserManualType(array $values = []) {
    $values += [
      'id' => $this->randomMachineName(),
      'label' => $this->randomMachineName()
    ];

    /** @var \Drupal\user_manual\Entity\UserManualTypeEntityInterface $user_manual_type */
    $user_manual_type = UserManualType::create($values);
    $user_manual_type->save();

    return $user_manual_type;
  }

  /**
   * Helper function to create user manual entity.
   */
  private function createUserManual(array $values = []) {
    // Populate defaults array.
    $values += [
      'field_manual' => [
        [
          'value' => $this->randomMachineName(32),
          'format' => filter_default_format(),
        ],
      ],
      'name' => $this->randomMachineName(8),
    ];

    if (!array_key_exists('user_id', $values)) {
      $user = User::load(\Drupal::currentUser()->id());
      if ($user) {
        $values['user_id'] = $user->id();
      }
      else {
        $values['user_id'] = 0;
      }
    }

    $user_manual = UserManual::create($values);
    $user_manual->save();

    return $user_manual;
  }

}
