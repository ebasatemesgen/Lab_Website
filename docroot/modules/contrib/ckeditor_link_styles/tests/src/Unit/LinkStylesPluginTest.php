<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor_link_styles\Unit;

use Drupal\ckeditor_link_styles\Plugin\CKEditor5Plugin\LinkStyles;
use Drupal\editor\EditorInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\ckeditor_link_styles\Plugin\CKEditor5Plugin\LinkStyles
 */
class LinkStylesPluginTest extends UnitTestCase {

  /**
   * Provides a list of configs to test.
   */
  public function providerGetDynamicPluginConfig(): array {
    return [
      'default configuration (empty)' => [
        [
          'styles' => [],
        ],
        [
          'style' => [
            'definitions' => [],
          ],
        ],
      ],
      'Simple' => [
        [
          'styles' => [
            ['label' => 'Button', 'element' => '<a class="btn">'],
          ],
        ],
        [
          'style' => [
            'definitions' => [
              [
                'name' => 'Button',
                'element' => 'a',
                'classes' => ['btn'],
              ],
            ],
          ],
        ],
      ],
      'Complex' => [
        [
          'styles' => [
            ['label' => 'Primary Button', 'element' => '<a class="btn btn-primary">'],
            ['label' => 'Secondary Button', 'element' => '<a class="btn btn-secondary">'],
          ],
        ],
        [
          'style' => [
            'definitions' => [
              [
                'name' => 'Primary Button',
                'element' => 'a',
                'classes' => ['btn', 'btn-primary'],
              ],
              [
                'name' => 'Secondary Button',
                'element' => 'a',
                'classes' => ['btn', 'btn-secondary'],
              ],
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * @covers ::getDynamicPluginConfig
   * @dataProvider providerGetDynamicPluginConfig
   */
  public function testGetDynamicPluginConfig(array $configuration, array $expected_dynamic_config): void {
    $plugin = new LinkStyles($configuration, 'ckeditor_link_styles_linkStyles', NULL);
    $dynamic_plugin_config = $plugin->getDynamicPluginConfig([], $this->prophesize(EditorInterface::class)->reveal());
    $this->assertSame($expected_dynamic_config, $dynamic_plugin_config);
  }

}
