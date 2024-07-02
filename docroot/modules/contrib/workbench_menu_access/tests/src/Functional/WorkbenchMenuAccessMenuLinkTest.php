<?php

namespace Drupal\Tests\workbench_menu_access\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\workbench_menu_access\Traits\WorkbenchMenuAccessTestTrait;

/**
 * Settings tests for the module.
 *
 * @group workbench_menu_access
 */
class WorkbenchMenuAccessMenuLinkTest extends BrowserTestBase {

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
   * Tests the menu link add/edit user interface.
   *
   * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
   */
  public function testMenuLinkPage(): void {
    $this->drupalLogin($this->admin);

    // Config check.
    $config = \Drupal::config('workbench_menu_access.settings');
    $active = $config->get('access_scheme');
    $this->assertEquals($active, '');

    // Check the settings.
    /** @var \Drupal\system\MenuInterface $menu */
    $menu = \Drupal::entityTypeManager()->getStorage('menu')->load('main');
    $this->assertEmpty($menu->getThirdPartySetting('workbench_menu_access', 'access_scheme'));

    // Add a menu link.
    $menu_link = $this->addMenuLink('My link', '', 'internal:/admin');
    $path_list = [
      '/admin/structure/menu/item/' . $menu_link->id() . '/edit',
      '/admin/structure/menu/item/' . $menu_link->id() . '/delete',
      '/admin/structure/menu/manage/main/add',
    ];

    foreach ($path_list as $test) {
      $this->drupalLogout();
      $this->drupalGet($test);
      $this->assertSession()->statusCodeEquals(403);

      $this->drupalLogin($this->editor);
      $this->drupalGet($test);
      $this->assertSession()->statusCodeEquals(200);

      $this->drupalLogin($this->admin);
      $this->drupalGet($test);
      $this->assertSession()->statusCodeEquals(200);
    }

    // Setup config.
    $scheme = $this->setUpTaxonomyScheme($this->nodeType, $this->vocabulary);
    $config = \Drupal::configFactory()->getEditable('workbench_menu_access.settings');
    $config->set('access_scheme', 'editorial_section')->save();

    // Save the form.
    $menu_path = "admin/structure/menu/manage/main/access";
    $this->drupalGet($menu_path);
    $edit = ['workbench_access', 3];
    $this->submitForm(['workbench_menu_access[]' => $edit], 'Save');

    foreach ($path_list as $test) {
      $this->drupalLogout();
      $this->drupalGet($test);
      $this->assertSession()->statusCodeEquals(403);

      $this->drupalLogin($this->editor);
      $this->drupalGet($test);
      $this->assertSession()->statusCodeEquals(403);

      $this->drupalLogin($this->admin);
      $this->drupalGet($test);
      $this->assertSession()->statusCodeEquals(200);
    }

    // Add editor to section.
    $this->addUserToSection($scheme, $this->editor, [3]);

    foreach ($path_list as $test) {
      $this->drupalLogout();
      $this->drupalGet($test);
      $this->assertSession()->statusCodeEquals(403);

      $this->drupalLogin($this->editor);
      $this->drupalGet($test);
      $this->assertSession()->statusCodeEquals(200);

      $this->drupalLogin($this->admin);
      $this->drupalGet($test);
      $this->assertSession()->statusCodeEquals(200);
    }

    // Test for subsection handling.
    // Save the form. Section 8 is a subsection of 3, so the editor should
    // still have access.
    $this->drupalLogin($this->admin);
    $this->drupalGet($menu_path);
    $edit = ['workbench_access', 8];
    $this->submitForm(['workbench_menu_access[]' => $edit], 'Save');

    // Editor can access the page.
    foreach ($path_list as $test) {
      $this->drupalLogout();
      $this->drupalGet($test);
      $this->assertSession()->statusCodeEquals(403);

      $this->drupalLogin($this->editor);
      $this->drupalGet($test);
      $this->assertSession()->statusCodeEquals(200);

      $this->drupalLogin($this->admin);
      $this->drupalGet($test);
      $this->assertSession()->statusCodeEquals(200);
    }
  }

}
