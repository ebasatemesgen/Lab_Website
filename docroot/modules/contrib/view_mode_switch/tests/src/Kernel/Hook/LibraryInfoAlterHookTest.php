<?php

namespace Drupal\Tests\view_mode_switch\Kernel\Hook;

use Drupal\Core\Asset\LibraryDiscoveryParser;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the 'hook_library_info_alter' hook implementation class.
 *
 * @coversDefaultClass \Drupal\view_mode_switch\Hook\LibraryInfoAlterHook
 * @group view_mode_switch
 */
class LibraryInfoAlterHookTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field_ui',
    'view_mode_switch',
  ];

  /**
   * The library discovery parser service.
   */
  protected LibraryDiscoveryParser $libraryDiscoveryParser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->libraryDiscoveryParser = $this->container->get('library.discovery.parser');
    $this->assertInstanceOf(LibraryDiscoveryParser::class, $this->libraryDiscoveryParser);
  }

  /**
   * Tests library information altered by view_mode_switch module.
   *
   * @covers ::libraryInfoAlter
   *
   * @see \view_mode_switch_library_info_alter()
   */
  public function testLibraryInfoAlter(): void {
    // @todo Remove dummy test for Drupal < 10.2 when module requires
    //   Drupal >= 10.2.
    // Tests for Drupal < 10.2.
    if (!version_compare(\Drupal::VERSION, '10.2', '>=')) {
      $this->assertTrue(TRUE);
    }
    // Tests for Drupal >= 10.2.
    else {
      $libraries = $this->libraryDiscoveryParser->buildByExtension('field_ui');

      $this->assertArrayHasKey('drupal.field_ui.manage_fields', $libraries);
      $this->assertArrayHasKey('dependencies', $libraries['drupal.field_ui.manage_fields']);
      $this->assertContains('view_mode_switch/field_ui.icons', $libraries['drupal.field_ui.manage_fields']['dependencies']);
    }
  }

}
