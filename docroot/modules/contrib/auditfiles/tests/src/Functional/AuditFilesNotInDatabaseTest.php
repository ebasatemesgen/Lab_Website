<?php

declare(strict_types=1);

namespace Drupal\Tests\auditfiles\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\user\UserInterface;

/**
 * Tests that the "Not in Database" report is reachable with no errors.
 *
 * @group auditfiles
 * @group auditfilesfunctional
 *
 * @covers \Drupal\auditfiles\Auditor\AuditFilesNotInDatabase
 * @covers \Drupal\auditfiles\Batch\AuditFilesNotInDatabaseBatchProcess
 * @covers \Drupal\auditfiles\Form\AuditFilesNotInDatabaseForm
 */
final class AuditFilesNotInDatabaseTest extends BrowserTestBase {

  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'field', 'file', 'user', 'auditfiles'];

  /**
   * User with admin privileges.
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
    $this->user = $this->drupalCreateUser(['access audit files reports']);

    // Create physical files.
    // Possible values: 'binary', 'html', 'image', 'javascript', 'php', 'sql',
    // 'text'.
    $this->getTestFiles('binary');
    $this->getTestFiles('html');
    $this->getTestFiles('image');
    $this->getTestFiles('javascript');
    $this->getTestFiles('php');
    $this->getTestFiles('sql');
    $this->getTestFiles('text');
  }

  /**
   * Tests report page returns correct HTTP response code.
   *
   * 403 for anonymous users (also for users without permission).
   * 200 for authenticated user with 'administer site configuration' perm.
   */
  public function testReportPage(): void {
    // Form to test.
    $path = Url::fromRoute('auditfiles.reports.notindatabase');
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
   * Tests that orphaned files display on the report.
   *
   * An "orphan" file is one in the file system that has no corresponding record
   * in the database.
   */
  public function testFileNotInDatabase(): void {
    // Form to test.
    $path = Url::fromRoute('auditfiles.reports.notindatabase');
    // Establish session.
    $session = $this->assertSession();
    // Log in as admin user.
    $this->drupalLogin($this->user);
    // Load the report page.
    $this->drupalGet($path);
    // Check for the report title.
    $session->pageTextContains("Not in database");
    // Check that the report table is not empty.
    $session->elementTextNotContains('css', '#edit-files', 'No items found');
    $session->pageTextNotContains("Found no files on the server not in the database");
    // Check that at least 36 files were found.
    $session->pageTextContains('Found at least 36 files on disk but not in the database.');
  }

  /**
   * Tests that an orphan file can be deleted.
   *
   * An "orphan" file is one in the file system that has no corresponding record
   * in the database.
   */
  public function testFileCanBeDeleted(): void {
    // Form to test.
    $path = Url::fromRoute('auditfiles.reports.notindatabase');
    // Establish session.
    $session = $this->assertSession();
    // Log in as admin user.
    $this->drupalLogin($this->user);
    // Load the report page.
    $this->drupalGet($path);
    // Check for the report title.
    $session->pageTextContains("Not in database");
    $session->elementExists('css', '[name="files[public://html-2.html]"]');
    // Check box for file to delete from server, and submit form.
    $edit = [
      'files[public://html-2.html]' => TRUE,
    ];
    $this->submitForm($edit, 'Delete selected items from the server');
    // Check for correct confirmation page and submit.
    $session->pageTextContains("Delete these files from the server?");
    $edit = [];
    $this->submitForm($edit, 'Confirm');
    // Check that target file is no longer listed.
    $session->pageTextContains("Not in database");
    $session->pageTextContains("Successfully deleted public://html-2.html from the server.");
    $session->elementNotExists('css', '[name="files[public://html-2.html]"]');
  }

  /**
   * Tests that orphan file system files can be added to the database.
   *
   * An "orphan" file is one in the file system that has no corresponding record
   * in the database.
   */
  public function testFileCanBeAdded(): void {
    // Form to test.
    $path = Url::fromRoute('auditfiles.reports.notindatabase');
    // Establish session.
    $session = $this->assertSession();
    // Log in as admin user.
    $this->drupalLogin($this->user);
    // Load the report page.
    $this->drupalGet($path);
    // Check for the report title.
    $session->pageTextContains("Not in database");
    $session->elementExists('css', '[name="files[public://image-1.png]"]');
    // Check box for file to add to database, and submit form.
    $edit = [
      'files[public://image-1.png]' => TRUE,
    ];
    $this->submitForm($edit, 'Add selected items to the database');
    // Check for correct confirmation page and submit.
    $session->pageTextContains("Add these files to the database?");
    $edit = [];
    $this->submitForm($edit, 'Confirm');
    // Check that target file is no longer listed.
    $session->pageTextContains("Not in database");
    $session->pageTextContains("Successfully added public://image-1.png to the database.");
    $session->elementNotExists('css', '[name="files[public://image-1.png]"]');
  }

}
