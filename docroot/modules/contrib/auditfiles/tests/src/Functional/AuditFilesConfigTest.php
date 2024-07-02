<?php

declare(strict_types=1);

namespace Drupal\Tests\auditfiles\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\user\UserInterface;

/**
 * Tests that the "Managed not used" report is reachable with no errors.
 *
 * @group auditfiles
 */
final class AuditFilesConfigTest extends BrowserTestBase {

  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'file', 'user', 'auditfiles'];

  /**
   * User with admin privileges.
   *
   * @var \Drupal\user\UserInterface
   */
  protected readonly UserInterface $user;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Create user with permissions to manage site configuration and access
    // audit files reports.
    $this->user = $this->drupalCreateUser(['configure audit files reports']);
  }

  /**
   * Tests config page returns correct HTTP response code.
   *
   * 403 for anonymous users (also for users without permission).
   * 200 for authenticated user with 'configure audit files reports' perm.
   */
  public function testReportPage(): void {
    // Form to test.
    $path = URL::fromRoute('auditfiles.configuration');
    // Establish session.
    $session = $this->assertSession();
    // Visit page as anonymous user, should receive a 403.
    $this->drupalGet($path);
    $session->statusCodeEquals(403);
    // Log in as admin user.
    $this->drupalLogin($this->user);
    // Test that report page returns a 200 response code.
    $this->drupalGet($path);
    $session->statusCodeEquals(200);
  }

  /**
   * Tests that the config page has correct settings.
   */
  public function testConfigPageContent(): void {
    // Form to test.
    $path = URL::fromRoute('auditfiles.configuration');
    // Establish session.
    $session = $this->assertSession();
    // Log in as admin user.
    $this->drupalLogin($this->user);
    // Load the report page.
    $this->drupalGet($path);
    // Check for the report title.
    $session->pageTextContains("Audit Files");
    // Check for form elements.
    $session->elementExists('css', '#edit-auditfiles-file-system-path');
    $session->optionExists('auditfiles_file_system_path', 'public');
    $session->optionExists('auditfiles_file_system_path', 'temporary');
    $session->elementExists('css', '#edit-auditfiles-exclude-files');
    $session->elementAttributeContains('css', '#edit-auditfiles-exclude-files', 'value', '.htaccess');
    $session->elementExists('css', '#edit-auditfiles-exclude-extensions');
    $session->elementAttributeContains('css', '#edit-auditfiles-exclude-extensions', 'value', '');
    $session->elementExists('css', '#edit-auditfiles-exclude-paths');
    $session->elementAttributeContains('css', '#edit-auditfiles-exclude-paths', 'value', 'color;css;ctools;js');
    $session->elementExists('css', '#edit-auditfiles-include-domains');
    $session->elementAttributeContains('css', '#edit-auditfiles-include-domains', 'value', '');
    $session->elementExists('css', '#edit-auditfiles-report-options-date-format');
    $session->elementExists('css', '#edit-auditfiles-report-options-date-format > option:nth-child(1)');
    $session->elementAttributeContains('css', '#edit-auditfiles-report-options-date-format > option:nth-child(1)', 'value', 'fallback');
    $session->elementExists('css', '#edit-auditfiles-report-options-items-per-page');
    $session->elementAttributeContains('css', '#edit-auditfiles-report-options-items-per-page', 'value', '50');
    $session->elementExists('css', '#edit-auditfiles-report-options-maximum-records');
    $session->elementAttributeContains('css', '#edit-auditfiles-report-options-maximum-records', 'value', '250');
    $session->elementNotExists('css', '#edit-auditfiles-report-options-batch-size');
  }

  /**
   * Tests that config page can be saved with default values without errors.
   */
  public function testConfigPageSaveConfig(): void {
    // Form to test.
    $path = URL::fromRoute('auditfiles.configuration');
    // Establish session.
    $session = $this->assertSession();
    // Log in as admin user.
    $this->drupalLogin($this->user);
    // Load the report page.
    $this->drupalGet($path);
    // Check for the report title.
    $session->pageTextContains("Audit Files");
    // Check that config page can be saved.
    // Build edit array.
    $edit = [
      'edit-auditfiles-file-system-path' => 'public',
      'edit-auditfiles-exclude-files' => '.htaccess',
      'edit-auditfiles-exclude-extensions' => '',
      'edit-auditfiles-exclude-paths' => 'color;css;ctools;js',
      'edit-auditfiles-include-domains' => '',
      'edit-auditfiles-report-options-date-format' => 'fallback',
      'edit-auditfiles-report-options-items-per-page' => '50',
      'edit-auditfiles-report-options-maximum-records' => '250',
    ];
    // Submit configuration form.
    $this->submitForm($edit, 'Save configuration');
    // Check that form saved successfully.
    $session->pageTextContains('The configuration options have been saved.');
    // Check page content.
    // Check for the report title.
    $session->pageTextContains("Audit Files");
    // Check for form elements.
    $session->fieldValueEquals('auditfiles_file_system_path', 'public');
    $session->elementAttributeContains('css', '#edit-auditfiles-exclude-files', 'value', '.htaccess');
    $session->elementAttributeContains('css', '#edit-auditfiles-exclude-extensions', 'value', '');
    $session->elementAttributeContains('css', '#edit-auditfiles-exclude-paths', 'value', 'color;css;ctools;js');
    $session->elementAttributeContains('css', '#edit-auditfiles-include-domains', 'value', '');
    $session->elementAttributeExists('css', '#edit-auditfiles-report-options-date-format > option:nth-child(1)', 'value');
    $session->elementAttributeContains('css', '#edit-auditfiles-report-options-date-format > option:nth-child(1)', 'value', 'fallback');
    $session->elementAttributeExists('css', '#edit-auditfiles-report-options-date-format > option:nth-child(1)', 'selected');
    $session->elementAttributeContains('css', '#edit-auditfiles-report-options-date-format > option:nth-child(1)', 'selected', 'selected');
    $session->elementAttributeContains('css', '#edit-auditfiles-report-options-items-per-page', 'value', '50');
    $session->elementAttributeContains('css', '#edit-auditfiles-report-options-maximum-records', 'value', '250');
  }

}
