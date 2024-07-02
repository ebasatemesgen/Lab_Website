<?php

namespace Drupal\view_mode_switch\Hook;

/**
 * Implements hook_library_info_alter().
 */
class LibraryInfoAlterHook {

  /**
   * Alter libraries provided by an extension.
   *
   * - Adds an icon for the 'view_mode_switch' field type on the 'Add field'
   *   screen in Field UI.
   *
   * @param array $libraries
   *   An associative array of libraries registered by $extension. Keyed by
   *   internal library name and passed by reference.
   * @param string $extension
   *   Can either be 'core' or the machine name of the extension that registered
   *   the libraries.
   *
   * @see \hook_library_info_alter()
   * @see \view_mode_switch_library_info_alter()
   */
  public function libraryInfoAlter(array &$libraries, string $extension): void {
    if ($extension === 'field_ui') {
      // Add additional styles for 'view_mode_switch' field type icon on
      // 'Add field' screen in Field UI.
      if (isset($libraries['drupal.field_ui.manage_fields'])) {
        $libraries['drupal.field_ui.manage_fields']['dependencies'][] = 'view_mode_switch/field_ui.icons';
      }
    }
  }

}
