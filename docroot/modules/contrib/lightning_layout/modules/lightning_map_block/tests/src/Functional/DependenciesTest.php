<?php

namespace Drupal\Tests\lightning_map_block\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * @group lightning_layout
 * @group lightning_map_block
 *
 * @requires module simple_gmap
 */
class DependenciesTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['lightning_map_block'];

  /**
   * Tests that Simple GMap is a soft dependency.
   */
  public function testSimpleGMap(): void {
    $account = $this->drupalCreateUser(['administer modules']);
    $this->drupalLogin($account);

    $this->drupalGet('/admin/modules/uninstall');
    $page = $this->getSession()->getPage();
    $page->checkField('Map block');
    $page->pressButton('Uninstall');
    $assert_session = $this->assertSession();
    $assert_session->pageTextNotContains('Simple Google Maps');
    $page->pressButton('Uninstall');
    $assert_session->pageTextContains('The selected modules have been uninstalled.');
    $this->drupalGet('/admin/modules');
    $assert_session->checkboxChecked('Simple Google Maps');

    // Ensure $this->container reflects the state of the site.
    $this->rebuildContainer();

    $component = $this->container->get('entity_display.repository')
      ->getViewDisplay('block_content', 'google_map')
      ->getComponent('field_map_location');
    $this->assertSame('simple_gmap', $component['type']);
  }

}
