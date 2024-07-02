<?php

namespace Drupal\Tests\workbench_menu_access\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\workbench_menu_access\Traits\WorkbenchMenuAccessTestTrait;

/**
 * Menu form tests for the module.
 *
 * @group workbench_menu_access
 */
class WorkbenchMenuAccessMenuTest extends BrowserTestBase {

  use WorkbenchMenuAccessTestTrait;

  /**
   * The default theme.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $admin;

  /**
   * Editorial user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $editor;

  /**
   * Vocabulary.
   *
   * @var \Drupal\taxonomy\Entity\Vocabulary
   */
  protected $vocabulary;

  /**
   * Vocabulary.
   *
   * @var \Drupal\node\Entity\NodeType
   */
  protected $nodeType;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'workbench_access',
    'workbench_menu_access',
    'node',
    'taxonomy',
    'options',
    'menu_link_content',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->nodeType = $this->createContentType(['type' => 'page']);
    $this->vocabulary = $this->setUpVocabulary();
    $this->setUpTaxonomyFieldForEntityType('node', 'page', (string) $this->vocabulary->id());
    $permissions = [
      'bypass workbench access',
      'administer workbench menu access',
      'administer menu',
    ];
    $this->admin = $this->setUpAdminUser($permissions);
    $this->editor = $this->setUpEditorUser();
    $this->createTerms($this->vocabulary);

  }

  /**
   * Test the menu admin user interface.
   */
  public function testMenuPage(): void {
    // Config check.
    $config = \Drupal::config('workbench_menu_access.settings');
    $active = $config->get('access_scheme');
    $this->assertEquals($active, '');

    // Check the settings.
    /** @var \Drupal\system\MenuInterface $menu */
    $menu = \Drupal::entityTypeManager()->getStorage('menu')->load('main');
    $this->assertEmpty($menu->getThirdPartySetting('workbench_menu_access', 'access_scheme'));

    // Access tests.
    $path = '/admin/structure/menu/manage/main';
    $access_path = '/admin/structure/menu/manage/main/access';
    $this->drupalGet($path);
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($access_path);
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalLogin($this->editor);
    $this->drupalGet($path);
    $web_assert = $this->assertSession();
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet($access_path);
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalLogin($this->admin);
    $this->drupalGet($path);
    $this->assertSession()->statusCodeEquals(200);
    $web_assert->fieldNotExists('workbench_menu_access[]');
    $this->drupalGet($access_path);
    $this->assertSession()->statusCodeEquals(200);
    $web_assert->pageTextContains('You must configure an access scheme to continue.');

    // Setup config.
    $scheme = $this->setUpTaxonomyScheme($this->nodeType, $this->vocabulary);
    $config = \Drupal::configFactory()->getEditable('workbench_menu_access.settings');
    $config->set('access_scheme', 'editorial_section')->save();

    // Admin can access the form.
    $this->drupalLogin($this->admin);
    $this->drupalGet($access_path);
    $this->assertSession()->statusCodeEquals(200);
    $web_assert->fieldExists('workbench_menu_access[]');

    // Save the form.
    $edit = ['workbench_access', 3];
    $this->submitForm(['workbench_menu_access[]' => $edit], 'Save');

    // Check the settings.
    /** @var \Drupal\system\MenuInterface $menu */
    $menu = \Drupal::entityTypeManager()->getStorage('menu')->load('main');
    $this->assertEquals(array_values($menu->getThirdPartySetting('workbench_menu_access', 'access_scheme')), $edit);

    // Editor can no longer access page.
    $this->drupalLogin($this->editor);
    $this->drupalGet($access_path);
    $this->assertSession()->statusCodeEquals(403);

    // Editor can access the menu page but not the access form.
    $this->addUserToSection($scheme, $this->editor, [3]);
    $this->drupalLogin($this->editor);
    $this->drupalGet($path);
    $this->assertSession()->statusCodeEquals(200);

    // Admin can access the form.
    $this->drupalLogin($this->admin);
    $this->drupalGet($access_path);
    $this->assertSession()->statusCodeEquals(200);
    $web_assert->fieldExists('workbench_menu_access[]');

    // Test for subsection handling.
    // Save the form. Section 8 is a subsection of 3, so the editor should
    // still have access.
    $edit = ['workbench_access', 8];
    $this->submitForm(['workbench_menu_access[]' => $edit], 'Save');

    // Editor can access the page but not the form.
    $this->drupalLogin($this->editor);
    $this->drupalGet($path);
    $this->assertSession()->statusCodeEquals(200);

  }

}
