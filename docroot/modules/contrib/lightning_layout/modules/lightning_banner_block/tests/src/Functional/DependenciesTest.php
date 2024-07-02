<?php

namespace Drupal\Tests\lightning_banner_block\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * @group lightning_layout
 * @group lightning_banner_block
 *
 * @requires module bg_image_formatter
 */
class DependenciesTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['lightning_banner_block'];

  /**
   * Tests that Background Image Formatter is a soft dependency.
   */
  public function testBgImageFormatter(): void {
    $account = $this->drupalCreateUser(['administer modules']);
    $this->drupalLogin($account);

    $this->drupalGet('/admin/modules/uninstall');
    $page = $this->getSession()->getPage();
    $page->checkField('Banner block');
    $page->pressButton('Uninstall');
    $assert_session = $this->assertSession();
    $assert_session->pageTextNotContains('Background Images Formatter');
    $page->pressButton('Uninstall');
    $assert_session->pageTextContains('The selected modules have been uninstalled.');
    $this->drupalGet('/admin/modules');
    $assert_session->checkboxChecked('Background Images Formatter');

    // Ensure $this->container reflects the state of the site.
    $this->rebuildContainer();

    $component = $this->container->get('entity_display.repository')
      ->getViewDisplay('block_content', 'banner')
      ->getComponent('field_banner_image');
    $this->assertSame('bg_image_formatter', $component['type']);
  }

}
