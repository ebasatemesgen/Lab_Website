<?php

namespace Drupal\fontawesome\Plugin\CKEditor4To5Upgrade;

use Drupal\ckeditor5\HTMLRestrictions;
use Drupal\ckeditor5\Plugin\CKEditor4To5UpgradePluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\filter\FilterFormatInterface;

  /**
   * @CKEditor4To5Upgrade(
   *   id = "ckeditor5_fontawesome",
   *   cke4_buttons = {
   *     "DrupalFontAwesome",
   *   },
   *   cke4_plugin_settings = {},
   *   cke5_plugin_elements_subset_configuration = {}
   * )
   *
   * @internal
   *   Plugin classes are internal.
   */
class FontAwesomeCKEditor5 extends PluginBase implements CKEditor4To5UpgradePluginInterface {

  /**
   * {@inheritdoc}
   */
  public function mapCKEditor4ToolbarButtonToCKEditor5ToolbarItem(string $cke4_button, HTMLRestrictions $text_format_html_restrictions): ?array {
    switch ($cke4_button) {
      case 'DrupalFontAwesome':
        return ['fontawesome'];

      default:
        throw new \OutOfBoundsException();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function mapCKEditor4SettingsToCKEditor5Configuration(string $cke4_plugin_id, array $cke4_plugin_settings): ?array {
    throw new \OutOfBoundsException();
  }

  /**
   * {@inheritdoc}
   */
  public function computeCKEditor5PluginSubsetConfiguration(string $cke5_plugin_id, FilterFormatInterface $text_format): ?array {
    throw new \OutOfBoundsException();
  }

}
