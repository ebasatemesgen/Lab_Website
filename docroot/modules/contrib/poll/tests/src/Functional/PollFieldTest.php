<?php

namespace Drupal\Tests\poll\Functional;


use Drupal\Tests\field_ui\Traits\FieldUiTestTrait;

/**
 * Tests the poll fields.
 *
 * @group poll
 */
class PollFieldTest extends PollTestBase {
  use FieldUiTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'field_ui',
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
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Add breadcrumb block.
    $this->drupalPlaceBlock('system_breadcrumb_block');
  }

  /**
   * Test poll entity fields.
   */
  public function testPollFields() {
    $poll = $this->poll;
    $this->drupalLogin($this->admin_user);
    // Add some fields.
    $this->fieldUIAddNewField('admin/config/content/poll', 'number', 'Number field', 'integer');
    $this->fieldUIAddNewField('admin/config/content/poll', 'text', 'Text field', 'string');
    // Test field form display.
    $this->drupalGet('admin/config/content/poll/form-display');
    $this->assertSession()->pageTextContains('Number field');
    $this->assertSession()->pageTextContains('Text field');
    // Test edit poll form.
    $this->drupalGet('poll/' . $poll->id() . '/edit');
    $this->assertSession()->pageTextContains('Number field');
    $this->assertSession()->pageTextContains('Text field');
    $edit = array(
      'field_number[0][value]' => random_int(10, 1000),
      'field_text[0][value]' => $this->randomString(),
    );
    $this->submitForm($edit, 'Save');
    // Test view poll form.
    $this->drupalGet('poll/' . $poll->id());
    $this->assertSession()->pageTextContains('Number field');
    $this->assertSession()->pageTextContains('Text field');
  }
}
