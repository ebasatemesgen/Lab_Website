<?php

namespace Drupal\Tests\lightning_layout\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the settings form.
 *
 * @group lightning_layout
 *
 * @requires module entity_block
 */
class SettingsFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test', 'lightning_layout', 'node'];

  /**
   * Tests the settings form.
   */
  public function testSettingsForm(): void {
    $account = $this->drupalCreateUser([], NULL, TRUE);
    $this->drupalLogin($account);

    // Without Entity Block, the settings form should be accessible, but not
    // expose any settings.
    $this->drupalGet('/admin/config/system/lightning/layout');
    $assert_session = $this->assertSession();
    $assert_session->pageTextContains('There are no settings available.');
    $assert_session->buttonNotExists('Save configuration');

    // Install Entity Block.
    $this->drupalGet('/admin/modules');
    $page = $this->getSession()->getPage();
    $page->checkField('Entity Block');
    $page->pressButton('Install');
    $assert_session->checkboxChecked('Entity Block');

    // The settings form should now allow users to expose certain entity types
    // as blocks, and our default configuration should be applied.
    $this->drupalGet('/admin/config/system/lightning/layout');
    $assert_session->checkboxNotChecked('Test entity');
    $assert_session->checkboxChecked('Content');
    $assert_session->checkboxChecked('User');

    // Ensure we can successfully change configuration.
    $page->checkField('Test entity');
    $page->uncheckField('User');
    $page->pressButton('Save configuration');
    $assert_session->pageTextContains('The configuration options have been saved.');
    $assert_session->checkboxChecked('Test entity');
    $assert_session->checkboxChecked('Content');
    $assert_session->checkboxNotChecked('User');

    $entity_blocks = $this->config('lightning_layout.settings')
      ->get('entity_blocks');
    $this->assertContains('entity_test', $entity_blocks);
    $this->assertContains('node', $entity_blocks);
    $this->assertNotContains('user', $entity_blocks);

    // Ensure $this->container reflects the state of the site, and the available
    // blocks are filtered according to config.
    // @see lightning_layout_block_alter()
    $this->rebuildContainer();
    $blocks = $this->container->get('plugin.manager.block')
      ->getDefinitions();
    $this->assertArrayHasKey('entity_block:entity_test', $blocks);
    $this->assertArrayHasKey('entity_block:node', $blocks);
    $this->assertArrayNotHasKey('entity_block:user', $blocks);
  }

}
