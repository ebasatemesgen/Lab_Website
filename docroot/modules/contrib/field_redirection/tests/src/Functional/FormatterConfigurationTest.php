<?php

namespace Drupal\Tests\field_redirection\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\field_redirection\Traits\FieldRedirectionTestTrait;

/**
 * Tests the field_redirection_formatter configuration forms.
 *
 * @todo Test possible formatter configurations and summaries.
 * A: Danger! The Redirect formatter should not be used with any view mode other
 *   than "Full content".
 * B: $this->t('HTTP status code: @code', ['@code' => $settings['code']]);
 * C: Will return 404 (page not found) if field is empty.
 * D: $this->t('Page restriction options: @pagerestriction',
 *   ['@pagerestriction' => $page_restrictions[$settings['page_restrictions']]]
 *   );
 *
 * @group field_redirection
 */
class FormatterConfigurationTest extends BrowserTestBase {

  use FieldRedirectionTestTrait;

  /**
   * Indicate which theme to use.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field_redirection',
    'field_ui',
    'link',
    'node',
  ];

  /**
   * The test content type to add fields.
   *
   * @var \Drupal\node\Entity\NodeType
   */
  protected $testContentType;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalLogin($this->rootUser);
    // Setup test content type and add a 'link' field.
    $this->testContentType = $this->setupContentTypeAndField();
  }

  /**
   * Tests danger message.
   */
  public function testDangerMessage() {
    // Enable the field's output.
    $this->drupalGet('admin/structure/types/manage/' . $this->testContentType->id() . '/display');
    $this->assertSession()->statusCodeEquals(200);
    $edit = [
      'fields[url][region]' => 'content',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Your settings have been saved');

    // Verify the 'danger' message displays correctly.
    $this->assertSession()
      ->pageTextContains('Danger! The Redirect formatter should not be used with any view mode other than "Full content".');

    // Turn on the "full" view mode.
    $this->drupalGet('admin/structure/types/manage/' . $this->testContentType->id() . '/display');
    $this->assertSession()->statusCodeEquals(200);
    $edit = [
      'display_modes_custom[full]' => TRUE,
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Your settings have been saved');

    // Make the field display.
    $this->drupalGet('admin/structure/types/manage/' . $this->testContentType->id() . '/display/full');
    $this->assertSession()->statusCodeEquals(200);
    $edit = [
      'fields[url][region]' => 'content',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Your settings have been saved');

    // Confirm that the danger message does not display.
    $this->assertSession()
      ->pageTextNotContains('Danger! The Redirect formatter should not be used with any view mode other than "Full content".');
  }

}
