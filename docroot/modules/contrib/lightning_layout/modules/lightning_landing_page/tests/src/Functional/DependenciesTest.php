<?php

namespace Drupal\Tests\lightning_landing_page\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * @group lightning_layout
 * @group lightning_landing_page
 *
 * @requires module layout_builder_restrictions
 * @requires module layout_library
 */
class DependenciesTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['lightning_landing_page'];

  /**
   * Tests soft dependencies of Lightning Landing Page.
   */
  public function testDependencies(): void {
    $account = $this->drupalCreateUser(['administer modules']);
    $this->drupalLogin($account);

    $this->drupalGet('/admin/modules/uninstall');
    $page = $this->getSession()->getPage();
    $page->checkField('Landing page');
    $page->pressButton('Uninstall');
    $assert_session = $this->assertSession();
    $assert_session->pageTextNotContains('Layout Builder Restrictions');
    $assert_session->pageTextNotContains('Layout library');
    $page->pressButton('Uninstall');
    $assert_session->pageTextContains('The selected modules have been uninstalled.');
    $this->drupalGet('/admin/modules');
    $assert_session->checkboxChecked('Layout Builder Restrictions');
    $assert_session->checkboxChecked('Layout library');

    // Ensure $this->container reflects the state of the site.
    $this->rebuildContainer();

    foreach (['full', 'default'] as $view_mode) {
      $third_parties = $this->container->get('entity_display.repository')
        ->getViewDisplay('node', 'landing_page', $view_mode)
        ->getThirdPartyProviders();
      $this->assertContains('layout_builder_restrictions', $third_parties);
      $this->assertContains('layout_library', $third_parties);
    }
  }

}
