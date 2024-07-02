<?php

namespace Drupal\Tests\lightning_landing_page\Functional;

use Drupal\layout_builder\Plugin\SectionStorage\OverridesSectionStorage;
use Drupal\layout_library\Entity\Layout;
use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests install-time logic of Lightning Landing Page.
 *
 * @group lightning_layout
 * @group lightning_landing_page
 *
 * @requires module layout_library
 */
class InstallTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['lightning_landing_page'];

  /**
   * Tests that Layout Builder overrides are enabled in the full node view mode.
   */
  public function testInstall() {
    $node = Node::create([
      'type' => 'landing_page',
    ]);
    $this->assertTrue($node->hasField(OverridesSectionStorage::FIELD_NAME));

    $account = $this->drupalCreateUser([
      'create landing_page content',
    ]);
    $this->drupalLogin($account);
    $this->drupalGet('/node/add/landing_page');
    $this->assertSession()->statusCodeEquals(200);
    // The Layout select should not be displayed because there is no Layout
    // for Landing pages.
    $this->assertSession()->fieldNotExists('Layout');

    // Add a layout for landing pages and assert the Layout select is present.
    Layout::create([
      'id' => 'test_layout',
      'label' => 'Test Layout',
      'targetEntityType' => 'node',
      'targetBundle' => 'landing_page',
    ])->save();
    $this->getSession()->reload();
    $this->assertSession()->optionExists('Layout', 'Test Layout');
  }

}
