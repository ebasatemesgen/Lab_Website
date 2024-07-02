<?php

namespace Drupal\Tests\lightning_media\Kernel;

use Drupal\entity_browser\Entity\EntityBrowser;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests Lightning Media's integration with Entity Browser.
 *
 * @group lightning_media
 *
 * @requires module entity_browser
 * @requires module inline_entity_form
 */
class EntityBrowserIntegrationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_browser',
    'entity_browser_example',
    'field',
    'file',
    'image',
    'lightning_media',
    'media',
    'node',
    'system',
    'text',
    'user',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // These are two separate calls because the config must be installed in this
    // specific order for the test to pass.
    $this->installConfig('node');
    $this->installConfig('entity_browser_example');
  }

  /**
   * Data provider for ::testInlineEntityFormDependency().
   *
   * @return array[]
   *   The test cases.
   */
  public function providerInlineEntityFormDependency(): array {
    return [
      ['file_upload'],
      ['embed_code'],
    ];
  }

  /**
   * Tests that EntityFormProxy-based widgets depend on Inline Entity Form.
   *
   * @param string $plugin_id
   *   The ID of the widget plugin to test.
   *
   * @dataProvider providerInlineEntityFormDependency
   */
  public function testInlineEntityFormDependency(string $plugin_id): void {
    // Without Inline Entity Form installed, the plugin should not exist.
    $this->assertFalse($this->container->get('plugin.manager.entity_browser.widget')->hasDefinition($plugin_id));

    $this->enableModules(['inline_entity_form']);
    $browser = EntityBrowser::load('test_files');
    $this->assertInstanceOf(EntityBrowser::class, $browser);

    $dependencies = $browser->getDependencies();
    $this->assertNotContains('inline_entity_form', $dependencies['module']);

    $browser->addWidget(['id' => $plugin_id]);
    $browser->calculateDependencies();
    $dependencies = $browser->getDependencies();
    $this->assertContains('inline_entity_form', $dependencies['module']);
  }

  /**
   * Tests that our libraries are not attached to custom entity browsers.
   */
  public function testLibraryAttachment(): void {
    /** @var \Drupal\entity_browser\EntityBrowserInterface $browser */
    $browser = EntityBrowser::load('test_files');
    $this->assertInstanceOf(EntityBrowser::class, $browser);

    $form = $this->container->get('form_builder')
      ->getForm($browser->getFormObject());
    $this->assertIsArray($form);
    $this->assertNotContains('lightning_media/browser.styling', $form['#attached']['library']);
  }

}
