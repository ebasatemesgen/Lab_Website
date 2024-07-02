<?php

namespace Drupal\Tests\workbench_menu_access\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\workbench_menu_access\Traits\WorkbenchMenuAccessTestTrait;

/**
 * Settings tests for the module.
 *
 * @group workbench_menu_access
 */
class WorkbenchMenuAccessSettingsTest extends BrowserTestBase {

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
    ];
    $this->admin = $this->setUpAdminUser($permissions);
    $this->editor = $this->setUpEditorUser();
  }

  /**
   * Tests the module configuration options.
   */
  public function testSettingsPage(): void {
    // Config check.
    $config = \Drupal::config('workbench_menu_access.settings');
    $active = $config->get('access_scheme');
    $this->assertEquals($active, '');

    // Access tests.
    $path = '/admin/config/workflow/workbench_access/menu_settings';
    $this->drupalGet($path);
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalLogin($this->editor);
    $this->drupalGet($path);
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalLogin($this->admin);
    $this->drupalGet($path);
    $this->assertSession()->statusCodeEquals(200);

    // Form tests.
    $this->assertSession()->pageTextContains('You must create an access scheme to continue.');
    $this->setUpTaxonomyScheme($this->nodeType, $this->vocabulary);
    $this->drupalGet($path);
    $this->assertSession()->pageTextNotContains('You must create an access scheme to continue.');

    $web_assert = $this->assertSession();
    $web_assert->optionExists('access_scheme', 'Editorial section');
    $this->submitForm(['access_scheme' => 'editorial_section'], 'Save');
    $option_field = $web_assert->optionExists('access_scheme', 'Editorial section');
    $this->assertTrue($option_field->hasAttribute('selected'), 'Item selected');

    $this->assertSession()->pageTextContains('The taxonomy scheme Editorial section is used for menu access.');

    // Config check.
    $config = \Drupal::config('workbench_menu_access.settings');
    $active = $config->get('access_scheme');
    $this->assertEquals($active, 'editorial_section');
  }

}
