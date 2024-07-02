<?php

namespace Drupal\Tests\lightning_api\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * @group lightning_api
 */
class OAuthKeyFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['lightning_api'];

  /**
   * Tests that the key generation form is unavailable if Simple OAuth is.
   */
  public function testFormUnavailableWithoutSimpleOAuth(): void {
    $account = $this->createUser([], NULL, TRUE);
    $this->drupalLogin($account);
    $this->drupalGet('/admin/config/system/lightning/api/keys');
    $this->assertSession()->statusCodeEquals(404);
  }

}
