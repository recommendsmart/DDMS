<?php

namespace Drupal\Tests\user_manual\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;
use Drupal\user_manual\UserManualAccessControlHandler;

/**
 * Test basic functionality of User Manual module.
 *
 * @group user_manual
 */
class ToolbarTest extends BrowserTestBase {

  use StringTranslationTrait;

  use TaxonomyTestTrait;

  const TEST_TERM_NAME='toolbar_test_term';

  /**
   * User without permission to view user manual.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $manualViewUser;

  /**
   * Term ID from test term.
   *
   * @var int
   */
  protected $testTermTid;

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
    'toolbar',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() :void {
    parent::setUp();
    $this->manualViewUser = $this->drupalCreateUser([
      'access toolbar',
      UserManualAccessControlHandler::VIEW_USER_MANUAL_PERMISSION,
    ]);
    $v = Vocabulary::load('manual_topics');
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $test_term = $this->createTerm($v, ['name' => self::TEST_TERM_NAME, 'langcode' => $langcode]);
    $this->testTermTid = $test_term->id();
  }

  /**
   * Verify that toolbar link exists.
   */
  public function testToolbarLink() {
    $this->drupalLogin($this->manualViewUser);
    $this->assertSession()->linkExists('User Manual');
    $this->assertSession()->linkByHrefExists('/admin/user-manual');
  }

  /**
   * Verify that taxonomy terms appear in the toolbar
   */
  public function testTopLevelTermsInToolbar() {
    $this->drupalLogin($this->manualViewUser);
    $this->assertSession()->linkExists(self::TEST_TERM_NAME);
    $this->assertSession()->linkByHrefExists("/admin/user-manual?field_manual_topics_target_id={$this->testTermTid}");
  }

}
