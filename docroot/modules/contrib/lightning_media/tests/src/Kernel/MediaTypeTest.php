<?php

namespace Drupal\Tests\lightning_media\Kernel;

use Drupal\Core\Serialization\Yaml;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;

/**
 * Tests of API-level Lightning functionality related to media types.
 *
 * @group lightning_media
 */
class MediaTypeTest extends KernelTestBase {

  use MediaTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'file',
    'image',
    'lightning_media',
    'media',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $values = file_get_contents(__DIR__ . '/../../../config/install/field.storage.media.field_media_in_library.yml');
    $values = Yaml::decode($values);
    FieldStorageConfig::create($values)->save();
  }

  /**
   * Tests that field_media_in_library is auto-cloned for new media bundles.
   */
  public function testCloneMediaInLibraryField() {
    $type = $this->createMediaType('file')->id();

    /** @var \Drupal\media\MediaInterface $media */
    $media = $this->container
      ->get('entity_type.manager')
      ->getStorage('media')
      ->create([
        'bundle' => $type,
      ]);

    $this->assertTrue($media->hasField('field_media_in_library'));

    // The field should be present in the form as a checkbox.
    $component = $this->container->get('entity_display.repository')
      ->getFormDisplay('media', $type)
      ->getComponent('field_media_in_library');

    $this->assertIsArray($component);
  }

}
