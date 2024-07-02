<?php

namespace Drupal\Tests\workbench_menu_access\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\workbench_menu_access\Traits\WorkbenchMenuAccessTestTrait;

/**
 * Test the node form.
 *
 * @group workbench_menu_access
 */
class WorkbenchMenuAccessNodeFormTest extends BrowserTestBase {

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
    'menu_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->nodeType = $this->createContentType(['type' => 'page']);
    $this->vocabulary = $this->setUpVocabulary();
    $this->setUpTaxonomyFieldForEntityType('node', 'page', (string) $this->vocabulary->id());
    $this->admin = $this->setUpAdminUser([
      'bypass workbench access',
      'administer workbench menu access',
      'administer menu',
      'administer content types',
      'create page content',
      'edit any page content',
      'delete any page content',
    ]);
    $this->editor = $this->setUpEditorUser();
    $this->createTerms($this->vocabulary);

  }

  /**
   * Tests the node form user interface.
   *
   * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
   */
  public function testNodeForm(): void {
    $menu_path = "admin/structure/menu/manage/main/access";

    $node_add_path = "node/add/page";
    $node_edit_path = "node/1/edit";

    $this->drupalLogin($this->admin);
    // Add menu options of the node type.
    $this->assertEmpty($this->nodeType->getThirdPartySetting('menu_ui', 'available_menus'));
    $edit = [
      'menu_options[main]' => 1,
      'menu_parent' => 'main:',
    ];
    $this->drupalGet('admin/structure/types/manage/page');
    $this->submitForm($edit, t('Save content type'));
    // Reload to check new settings.
    /** @var \Drupal\node\Entity\NodeType $type */
    $type = \Drupal::entityTypeManager()->getStorage('node_type')->load('page');
    $this->assertNotEmpty($type->getThirdPartySetting('menu_ui', 'available_menus'));

    // Config check.
    $config = \Drupal::config('workbench_menu_access.settings');
    $active = $config->get('access_scheme');
    $this->assertEquals($active, '');

    // Check the settings.
    /** @var \Drupal\system\MenuInterface $menu */
    $menu = \Drupal::entityTypeManager()->getStorage('menu')->load('main');
    $this->assertEmpty($menu->getThirdPartySetting('workbench_menu_access', 'access_scheme'));

    $new_menu_name = 'new';
    $this->addMenu($new_menu_name);
    /** @var \Drupal\system\MenuInterface $new_menu */
    $new_menu = \Drupal::entityTypeManager()->getStorage('menu')->load($new_menu_name);
    $this->assertEmpty($new_menu->getThirdPartySetting('workbench_menu_access', 'access_scheme'));

    // Check node forms as admin and editor.
    // Nothing is configured to restrict access.
    $this->drupalLogin($this->admin);
    $this->drupalGet($node_add_path);
    $this->assertSession()->pageTextContains('Provide a menu link');

    $this->checkOptions('empty');

    $this->drupalLogin($this->editor);
    $this->drupalGet($node_add_path);
    $this->assertSession()->pageTextContains('Provide a menu link');

    $this->checkOptions('empty');

    // Add menu links to main.
    $this->addMenuLink('My link 1', '', 'internal:/admin');
    $this->addMenuLink('My link 2', '', 'internal:/user');

    // Add menu links to new.
    $this->addMenuLink('My link 3', '', 'internal:/admin', $new_menu_name);
    $this->addMenuLink('My link 4', '', 'internal:/user', $new_menu_name);

    // Setup config.
    $scheme = $this->setUpTaxonomyScheme($this->nodeType, $this->vocabulary);
    $config = \Drupal::configFactory()->getEditable('workbench_menu_access.settings');
    $config->set('access_scheme', 'editorial_section')->save();

    // When we set the scheme, the editor cannot create content anymore unless
    // they have a section assignment. This assignment will not match that used
    // for menu editing.
    $this->addUserToSection($scheme, $this->editor, [9]);

    // Save the form.
    $this->drupalLogin($this->admin);
    $this->drupalGet($menu_path);
    $edit = ['workbench_access', 3];
    $this->submitForm(['workbench_menu_access[]' => $edit], 'Save');

    // Check the status.
    /** @var \Drupal\system\MenuInterface $menu */
    $menu = \Drupal::entityTypeManager()->getStorage('menu')->load('main');
    $this->assertNotEmpty($menu->getThirdPartySetting('workbench_menu_access', 'access_scheme'));

    // Check node forms as admin and editor.
    // Main menu is configured to restrict access.
    $this->drupalGet($node_add_path);
    $this->assertSession()->pageTextContains('Provide a menu link');

    $this->checkOptions('main');

    $this->drupalLogin($this->editor);
    $this->drupalGet($node_add_path);
    $this->assertSession()->pageTextNotContains('Provide a menu link');

    // Expand the menu options of the node type.
    $this->drupalLogin($this->admin);
    $edit = [
      'menu_options[new]' => 1,
      'menu_options[main]' => 1,
      'menu_parent' => 'main:',
    ];
    $this->drupalGet('admin/structure/types/manage/page');
    $this->submitForm($edit, t('Save content type'));

    // Check node forms as admin and editor.
    // Main menu is configured to restrict access but 'new' is not and should
    // return data.
    $this->drupalLogin($this->editor);
    $this->drupalGet($node_add_path);
    $this->assertSession()->pageTextContains('Provide a menu link');

    $this->checkOptions('new');

    $this->drupalLogin($this->admin);
    $this->drupalGet($node_add_path);
    $this->assertSession()->pageTextContains('Provide a menu link');

    $this->checkOptions('all');

    // Save the node and check edit form.
    $this->drupalGet($node_add_path);
    $edit = [
      'title[0][value]' => 'Node 1',
      'field_workbench_access' => 9,
      'menu[enabled]' => 1,
      'menu[title]' => 'Node 1',
      'menu[menu_parent]' => 'main:',
    ];
    $this->submitForm($edit, 'Save');

    // Check node forms as admin and editor.
    // Main menu is configured to restrict access but 'new' is not and should
    // return data.
    $this->drupalGet($node_edit_path);
    $this->assertSession()->pageTextContains('Provide a menu link');

    $this->checkOptions('all');

    $this->drupalLogin($this->editor);
    $this->drupalGet($node_edit_path);
    $this->assertSession()->pageTextContains('You may not edit the menu this content is assigned to.');

    // Remove new from the options.
    $this->drupalLogin($this->admin);
    $edit = [
      'menu_options[new]' => 0,
      'menu_options[main]' => 1,
      'menu_parent' => 'main:',
    ];
    $this->drupalGet('admin/structure/types/manage/page');
    $this->submitForm($edit, t('Save content type'));

    // Add editor to the menu section.
    $this->addUserToSection($scheme, $this->editor, [3]);

    $this->drupalGet($node_edit_path);
    $this->assertSession()->pageTextContains('Provide a menu link');

    $this->checkOptions('main');

    $this->drupalLogin($this->editor);
    $this->drupalGet($node_edit_path);
    $this->assertSession()->pageTextContains('Provide a menu link');

    $this->checkOptions('main');

    // Check node forms as admin and editor.
    // Main menu is configured to restrict access and the editor has it.
    $this->drupalGet($node_add_path);
    $this->assertSession()->pageTextContains('Provide a menu link');

    $this->checkOptions('main', '-- Node 1');

    $this->drupalLogin($this->editor);
    $this->drupalGet($node_add_path);
    $this->assertSession()->pageTextContains('Provide a menu link');

    $this->checkOptions('main', '-- Node 1');
  }

  /**
   * Validates that menu options are correct based on permissions.
   *
   * @param string $set
   *   The name of the expected menu options set.
   * @param string $add_link
   *   An additional link to add to the 'main' set after saving a node.
   */
  public function checkOptions($set, $add_link = NULL): void {
    $sets = [];
    $sets['empty'] = [
      '<Main navigation>',
    ];
    $sets['main'] = [
      '<Main navigation>',
      '-- My link 1',
      '-- My link 2',
    ];
    if (!is_null($add_link)) {
      $sets['main'][] = $add_link;
    }
    $sets['new'] = [
      '<new>',
      '-- My link 3',
      '-- My link 4',
    ];
    $sets['all'] = array_merge($sets['main'], $sets['new']);

    $web_assert = $this->assertSession();

    foreach ($sets[$set] as $option) {
      $web_assert->optionExists('menu[menu_parent]', $option);
    }
    if ($set == 'empty' || $set == 'main') {
      foreach ($sets['new'] as $option) {
        $web_assert->optionNotExists('menu[menu_parent]', $option);
      }
    }
    if ($set == 'new') {
      foreach ($sets['main'] as $option) {
        $web_assert->optionNotExists('menu[menu_parent]', $option);
      }
    }

  }

}
