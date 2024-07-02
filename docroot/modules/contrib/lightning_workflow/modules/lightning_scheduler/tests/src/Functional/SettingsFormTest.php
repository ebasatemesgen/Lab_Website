<?php

namespace Drupal\Tests\lightning_scheduler\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Lightning Scheduler settings form.
 *
 * @group lightning_scheduler
 * @group lightning_workflow
 */
class SettingsFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['lightning_scheduler'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that administrators can access the settings form.
   */
  public function testAccess(): void {
    $account = $this->drupalCreateUser([], NULL, TRUE);
    $this->drupalLogin($account);
    $this->drupalGet('/admin/config/system/lightning/scheduler');
    $this->assertSession()->statusCodeEquals(200);
  }

}
