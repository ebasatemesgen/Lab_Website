<?php

namespace Drupal\Tests\view_mode_switch\Unit\Hook;

use Drupal\Tests\UnitTestCase;
use Drupal\view_mode_switch\Hook\LibraryInfoAlterHook;

/**
 * Tests the 'hook_library_info_alter' hook implementation class.
 *
 * @coversDefaultClass \Drupal\view_mode_switch\Hook\LibraryInfoAlterHook
 * @group view_mode_switch
 */
class LibraryInfoAlterHookTest extends UnitTestCase {

  /**
   * Data provider for testing altering libraries provided by an extension.
   *
   * @return array
   *   The test data.
   */
  public function dataProviderLibraryInfoAlter(): array {
    return [
      'field_ui/drupal.field_ui.manage_fields' => [
        [
          'drupal.field_ui.manage_fields' => [
            'dependencies' => [
              'test/test',
            ],
          ],
        ],
        'field_ui',
        [
          'drupal.field_ui.manage_fields' => [
            'dependencies' => [
              'test/test',
              'view_mode_switch/field_ui.icons',
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * Tests altering libraries provided by an extension.
   *
   * @param array $libraries
   *   An associative array of libraries registered by $extension. Keyed by
   *   internal library name and passed by reference.
   * @param string $extension
   *   Can either be 'core' or the machine name of the extension that registered
   *   the libraries.
   * @param array $expected
   *   The expected value.
   *
   * @covers ::libraryInfoAlter
   *
   * @dataProvider dataProviderLibraryInfoAlter
   */
  public function testLibraryInfoAlter(array $libraries, string $extension, array $expected): void {
    // Prepare class mock.
    $class = $this->createClassMock();

    $class->libraryInfoAlter($libraries, $extension);

    $this->assertEquals($expected, $libraries);
  }

  /**
   * Creates and returns a test class mock.
   *
   * @param array $only_methods
   *   An array of names for methods to be configurable.
   *
   * @return \Drupal\view_mode_switch\Hook\LibraryInfoAlterHook|\PHPUnit\Framework\MockObject\MockObject
   *   The mocked class.
   */
  protected function createClassMock(array $only_methods = []) {
    return $this->getMockBuilder(LibraryInfoAlterHook::class)
      ->disableOriginalConstructor()
      ->onlyMethods($only_methods)
      ->getMock();
  }

}
