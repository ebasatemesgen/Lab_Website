<?php

namespace Drupal\Tests\lightning_media_image\Kernel;

use Drupal\entity_browser\Entity\EntityBrowser;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\image\Kernel\ImageFieldCreationTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;

/**
 * @group lightning_media
 * @group lightning_media_image
 *
 * @requires module entity_browser
 */
class ImageBrowserConfigTest extends KernelTestBase {

  use ContentTypeCreationTrait;
  use ImageFieldCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'file',
    'image',
    'lightning_media_image',
    'node',
    'system',
    'text',
    'user',
  ];

  /**
   * Tests that the image browser is automatically used for a new image field.
   */
  public function testImageBrowserAddedToEntityFormDisplay(): void {
    $this->installConfig('node');
    $this->installEntitySchema('node');
    $this->createContentType(['type' => 'test']);

    // Entity Browser is not installed, so adding a new image field should not
    // change the form display.
    $this->createImageField('field_image1', 'test');
    $component = $this->container->get('entity_display.repository')
      ->getFormDisplay('node', 'test')
      ->getComponent('field_image1');
    $this->assertSame('image_image', $component['type']);

    // If we enable Entity Browser, but don't have the image browser installed,
    // we should still not have any changes.
    $this->enableModules(['entity_browser']);
    $this->createImageField('field_image2', 'test');
    $component = $this->container->get('entity_display.repository')
      ->getFormDisplay('node', 'test')
      ->getComponent('field_image2');
    $this->assertSame('image_image', $component['type']);

    // If the image browser exists, new image fields should use it.
    EntityBrowser::create(['name' => 'image_browser'])
      ->setDisplay('standalone')
      ->setWidgetSelector('single')
      ->setSelectionDisplay('no_display')
      ->save();
    $this->createImageField('field_image3', 'test');
    $component = $this->container->get('entity_display.repository')
      ->getFormDisplay('node', 'test')
      ->getComponent('field_image3');
    $this->assertSame('entity_browser_file', $component['type']);
    $this->assertSame('image_browser', $component['settings']['entity_browser']);
  }

}
