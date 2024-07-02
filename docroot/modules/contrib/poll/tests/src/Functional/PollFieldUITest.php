<?php

namespace Drupal\Tests\poll\Functional;

/**
 * Tests the poll field UI.
 *
 * @group poll
 */
class PollFieldUITest extends PollTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'field_ui',
    'help',
  ];

  /**
   * {@inheritdoc}
   */
  protected $adminPermissions = [
    'administer poll form display',
    'administer poll display',
    'administer poll fields',
    'administer polls',
    'access polls',
    'access administration pages',
    'administer blocks',
    'administer permissions',
  ];

  /**
   * Test if 'Manage fields' page is visible in the poll's settings UI.
   */
  public function testPollFieldUI() {

    $this->drupalLogin($this->admin_user);
    $this->drupalGet('admin/config/content/poll');
    $this->assertSession()->statusCodeEquals(200);

    // Check if 'Manage fields' tab appears in the poll's settings page.
    $this->assertSession()->addressEquals('admin/config/content/poll');
    $this->assertSession()->pageTextContains('Manage fields');

    // Ensure that the 'Manage display' page is visible.
    $this->clickLink('Manage display');
    $this->assertSession()->titleEquals('Manage display | Drupal');

    // Ensure vote results in List
    $element = $this->cssSelect('#poll-votes');
    $this->assertNotEquals($element, array(), '"Vote form/Results" field is available.');

    // Ensure that the 'Manage fields' page is visible.
    $this->clickLink('Manage fields');
    $this->assertSession()->titleEquals('Manage fields | Drupal');

    // Add a poll field.
    $this->clickLink('Add field');
    $edit = [
      'new_storage_type' => 'field_ui:entity_reference:user',
      'label' => 'poll',
      'field_name' => 'poll',
    ];
    $this->submitForm($edit, 'Save and continue');

    $edit = [
      'settings[target_type]' => 'poll',
    ];
    $this->submitForm($edit, 'Save field settings');
    $this->assertSession()->pageTextContains('Updated field poll field settings.');

    $edit = [
      'label' => 'field_poll',
    ];
    $this->submitForm($edit, 'Save settings');
    $this->assertSession()->pageTextContains('Saved field_poll configuration.');

    // Ensure that the newly created field is listed.
    $this->assertSession()->pageTextContains($edit['label']);
  }

  /**
   * Tests if the links on the Poll Help-page are working properly.
   */
  function testPollHelpLinks() {
    $this->drupalGet('admin/help/poll');

    $this->clickLink('Poll module');
    $this->assertSession()->addressEquals('https://www.drupal.org/docs/contributed-modules/poll');
    $this->drupalGet('admin/help/poll');

    $this->clickLink('Add a poll');
    $this->assertSession()->addressEquals('poll/add');
    $this->drupalGet('admin/help/poll');

    $this->clickLink('Polls', 0);
    $this->assertSession()->addressEquals('admin/content/poll');
    $this->drupalGet('admin/help/poll');

    $this->clickLink('Polls', 1);
    $this->assertSession()->addressEquals('admin/content/poll');
    $this->drupalGet('admin/help/poll');

    $this->clickLink('Blocks administration page');
    $this->assertSession()->addressEquals('admin/structure/block');
    $this->drupalGet('admin/help/poll');

    $this->clickLink('Configure Poll permissions');
    $this->assertSession()->pageTextContains('Access the Poll overview page');
  }
}
