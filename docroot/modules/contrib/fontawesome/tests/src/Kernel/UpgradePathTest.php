<?php

declare(strict_types = 1);

namespace Drupal\Tests\fontawesome\Kernel;

use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Tests\ckeditor5\Kernel\SmartDefaultSettingsTest;

/**
 * @covers \Drupal\fontawesome\Plugin\CKEditor4To5Upgrade\FontAwesomeCKEditor5
 * @group fontawesome
 * @group ckeditor5
 * @requires module ckeditor5
 * @internal
 */
class UpgradePathTest extends SmartDefaultSettingsTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'fontawesome',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();


    $filter_config = [
      'filter_html' => [
        'status' => 1,
        'settings' => [
          'allowed_html' => '<p> <br> <strong>',
        ],
      ],
    ];

    FilterFormat::create([
      'format' => 'font_awesome_enabled',
      'name' => 'Font Awesome Enabled',
      'filters' => $filter_config,
    ])->setSyncing(TRUE)->save();


    $editor_settings =  [
        'toolbar' => [
          'rows' => [
            0 => [
              [
                'name' => 'Basic Formatting',
                'items' => [
                  'Bold',
                  'Format',
                ],
              ],
              [
                'name' => 'Embedding',
                'items' => ['DrupalFontAwesome'],
              ],
            ],
          ],
        ],
        'plugins' => [],
      ];


    Editor::create([
      'format' => 'font_awesome_enabled',
      'editor' => 'ckeditor',
      'settings' => $editor_settings,
    ])->setSyncing(TRUE)->save();
  }

  /**
   * {@inheritdoc}
   */
  public function provider() {
    $expected_ckeditor5_toolbar = [
      'items' => [
        'bold',
        '|',
        'fontawesome',
      ],
    ];


    yield "Fontawesome enabled" => [
      'format_id' => 'font_awesome_enabled',
      'filters_to_drop' => [],
      'expected_ckeditor5_settings' => [
        'toolbar' => $expected_ckeditor5_toolbar,
        'plugins' => [],
      ],
      'expected_superset' => '<i class data-fa-transform> <span class data-fa-transform>',
      'expected_fundamental_compatibility_violations' => [],
      'expected_db_logs' => [],
      'expected_messages' => [
        'warning' => [
          'Updating to CKEditor 5 added support for some previously unsupported tags/attributes. A plugin introduced support for the following:  The tags <em class="placeholder">&lt;i&gt;, &lt;span&gt;</em>; These attributes: <em class="placeholder"> class (for &lt;i&gt;, &lt;span&gt;), data-fa-transform (for &lt;i&gt;, &lt;span&gt;)</em>; Additional details are available in your logs.'
        ],
      ],
    ];
  }

}
