<?php

namespace Drupal\Tests\lightning_media\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Lightning Media settings form.
 *
 * @group lightning_media
 */
class SettingsFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['lightning_media'];

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
    $this->drupalGet('/admin/config/system/lightning/media');
    $this->assertSession()->statusCodeEquals(200);
  }

}
