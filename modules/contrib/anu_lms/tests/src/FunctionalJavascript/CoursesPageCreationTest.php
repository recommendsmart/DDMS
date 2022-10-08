<?php

namespace Drupal\Tests\anu_lms\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Test the courses pages.
 *
 * @group anu_lms
 */
class CoursesPageCreationTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'anu_lms',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'claro';

  /**
   * Set to TRUE to strict check all configuration saved.
   *
   * @var bool
   *
   * @see \Drupal\Core\Config\Testing\ConfigSchemaChecker
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Rebuild router to get all custom routes.
    $this->container->get('router.builder')->rebuild();

    // Uninstall dynamic_page_cache module to avoid issues with testing the
    // updated content after editing nodes.
    $this->container->get('module_installer')->uninstall(['dynamic_page_cache']);
  }

  /**
   * Test everything related to courses page.
   */
  public function testCoursesPageCreation() {
    $account = $this->drupalCreateUser([], 'test', TRUE);
    $this->drupalLogin($account);

    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    // 1. Course category taxonomy.
    $this->drupalGet('admin/structure/taxonomy/manage/course_category/add');

    // Add "People" term.
    $page->fillField('Name', 'People');
    $page->pressButton('Save');

    // Add "Animals" term.
    $page->fillField('Name', 'Animals');
    $page->pressButton('Save');

    // 2. Create courses.
    $courses = [
      [
        'category' => '1',
        'titles' => [
          'British', 'England',
        ],
      ], [
        'category' => '2',
        'titles' => [
          'Birds', 'Fishes', 'Snakes',
        ],
      ],
    ];
    foreach ($courses as $course) {
      foreach ($course['titles'] as $title) {
        $this->drupalGet('node/add/course');

        $page->fillField('Title', $title);

        // Upload image.
        $imagePath = __DIR__ . '/assets/example.png';
        $page->attachFileToField('Add a new file', $imagePath);
        $assert->assertWaitOnAjaxRequest();
        $page->fillField('Alternative text', 'Example image');

        // Set category.
        $page->selectFieldOption('Category', $course['category']);

        // Set alias.
        $page->findById('edit-path-0')->click();
        $page->fillField('URL alias', '/course/' . strtolower($title));

        // Go to "Modules" tab.
        $page->findLink('Modules')->click();

        // Set title for 1st Module.
        $page->fillField('field_course_module[0][subform][field_module_title][0][value]', 'Module 1');

        // Add an empty lesson.
        $page->pressButton('Add new lesson');
        $assert->assertWaitOnAjaxRequest();
        $page->fillField('field_course_module[0][subform][field_module_lessons][form][0][title][0][value]', 'Lesson 1');
        $page->pressButton('Create lesson');
        $assert->assertWaitOnAjaxRequest();

        // Save the course.
        $page->pressButton('Save');
      }
    }

    // 2. Create courses page.
    $this->drupalGet('node/add/courses_page');

    $coursesPageTitle = 'Know more about people';
    $page->fillField('Title', $coursesPageTitle);

    // Set category.
    $page->selectFieldOption('Category', '1');

    // Set alias.
    $page->findById('edit-path-0')->click();
    $page->fillField('URL alias', '/courses/' . strtolower($coursesPageTitle));
    $page->pressButton('Save');

    // Make sure that the courses page was added.
    $title = $assert->waitForElementVisible('css', '#anu-application h1');
    $this->assertNotEmpty($title);
    $this->assertSame($coursesPageTitle, $title->getText());

    // 3. Make sure that "Know more about people" includes only 1 category and
    // 2 courses.
    $filterCategories = $page->findAll('css', '[data-test=anu-lms-courses-category-filter] span.MuiChip-label');
    $this->assertSame('All categories', $filterCategories[0]->getText());
    $this->assertSame('People', $filterCategories[1]->getText());
    $this->assertSame(2, count($filterCategories));

    $coursesList = $page->findAll('css', '[data-test=anu-lms-courses-list] h3');
    $this->assertSame('British', $coursesList[0]->getText());
    $this->assertSame('England', $coursesList[1]->getText());
    $this->assertSame(2, count($coursesList));

    // 4. Add a category "Animals" to "Know more about people" and make sure new
    // lessons and the category will be added to that courses page.
    $node = $this->drupalGetNodeByTitle('Know more about people');
    $this->drupalGet('node/' . $node->id() . '/edit');

    // Add "Animals" category.
    $page->findButton('Add Course category')->click();
    $assert->assertWaitOnAjaxRequest();
    $page->selectFieldOption('Category', '2');

    // Save changes.
    $page->pressButton('Save');

    // Make sure that category "Animals" was added.
    $filterCategories = $page->findAll('css', '[data-test=anu-lms-courses-category-filter] span.MuiChip-label');
    $this->assertSame('All categories', $filterCategories[0]->getText());
    $this->assertSame('People', $filterCategories[1]->getText());
    $this->assertSame('Animals', $filterCategories[2]->getText());
    $this->assertSame(3, count($filterCategories));

    // Make sure that all courses are there.
    $categoriesTitles = $page->findAll('css', '[data-test=anu-lms-courses-list] h2');
    $this->assertSame('People', $categoriesTitles[0]->getText());
    $this->assertSame('Animals', $categoriesTitles[1]->getText());
    $this->assertSame(2, count($categoriesTitles));

    // Make sure that all lessons are there.
    $coursesList = $page->findAll('css', '[data-test=anu-lms-courses-list] h3');
    $this->assertSame('British', $coursesList[0]->getText());
    $this->assertSame('England', $coursesList[1]->getText());
    $this->assertSame('Birds', $coursesList[2]->getText());
    $this->assertSame('Fishes', $coursesList[3]->getText());
    $this->assertSame('Snakes', $coursesList[4]->getText());
    $this->assertSame(5, count($coursesList));

    // 5. Change order of categories to "Animals" -> "People" for "Know more
    // about people".
    $node = $this->drupalGetNodeByTitle('Know more about people');
    $this->drupalGet('node/' . $node->id() . '/edit');

    // Remove 1st one (People) and add that category at the end instead of sort
    // of categories.
    $paragraphToggleIcon = $assert->waitForElementVisible('css', '#edit-field-courses-content-0-top .paragraphs-dropdown-toggle');
    $paragraphToggleIcon->click();
    $removeButton = $assert->waitForElementVisible('css', '#field-courses-content-0-remove');
    $removeButton->click();
    $assert->assertWaitOnAjaxRequest();

    $page->findButton('Add Course category')->click();
    $assert->assertWaitOnAjaxRequest();
    $page->selectFieldOption('Category', '1');

    // Save changes.
    $page->pressButton('Save');

    // Make sure that category "Animals" is located before "People" in filters.
    $filterCategories = $page->findAll('css', '[data-test=anu-lms-courses-category-filter] span.MuiChip-label');
    $this->assertSame('All categories', $filterCategories[0]->getText());
    $this->assertSame('Animals', $filterCategories[1]->getText());
    $this->assertSame('People', $filterCategories[2]->getText());
    $this->assertSame(3, count($filterCategories));

    // Make sure that category "Animals" is located before "People" in courses
    // list.
    $categoriesTitles = $page->findAll('css', '[data-test=anu-lms-courses-list] h2');
    $this->assertSame('Animals', $categoriesTitles[0]->getText());
    $this->assertSame('People', $categoriesTitles[1]->getText());
    $this->assertSame(2, count($categoriesTitles));

    // Make sure that lessons from "Animals" category is located before lessons
    // from "People" category.
    $coursesList = $page->findAll('css', '[data-test=anu-lms-courses-list] h3');
    $this->assertSame('Birds', $coursesList[0]->getText());
    $this->assertSame('Fishes', $coursesList[1]->getText());
    $this->assertSame('Snakes', $coursesList[2]->getText());
    $this->assertSame('British', $coursesList[3]->getText());
    $this->assertSame('England', $coursesList[4]->getText());
    $this->assertSame(5, count($coursesList));

    // 6. Set "Courses must be completed in this order" for "Animals" category.
    $this->drupalGet('taxonomy/term/2/sort-courses');
    $page->checkField('Courses must be completed in this order');
    $page->pressButton('Save');
    $this->drupalGet('taxonomy/term/2/sort-courses');

    // Go to the "Know more about people" page.
    $node = $this->drupalGetNodeByTitle('Know more about people');
    $this->drupalGet($node->toUrl());

    // Make sure that lessons from "Fishes" and "Snakes" courses are disabled.
    $coursesList = $page->findAll('css', '[data-test=anu-lms-courses-list] a');
    $this->assertSame('false', $coursesList[0]->getAttribute('aria-disabled'));
    $this->assertSame('true', $coursesList[1]->getAttribute('aria-disabled'));
    $this->assertSame('true', $coursesList[2]->getAttribute('aria-disabled'));
    $this->assertSame('false', $coursesList[3]->getAttribute('aria-disabled'));
    $this->assertSame('false', $coursesList[4]->getAttribute('aria-disabled'));

    // Make sure that "Fishes" and "Snakes" are disabled there as well.
    $courses = ['Fishes', 'Snakes'];
    foreach ($courses as $course) {
      $node = $this->drupalGetNodeByTitle($course);
      $this->drupalGet($node->toUrl());
      $body = $page->find('css', '.page-content');
      $this->assertNotEmpty($body);
      $this->assertStringContainsString('This course is locked.', $body->getText());
    }
  }

}
