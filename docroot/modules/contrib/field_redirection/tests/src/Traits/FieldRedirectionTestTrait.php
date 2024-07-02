<?php

namespace Drupal\Tests\field_redirection\Traits;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\link\LinkItemInterface;

/**
 * Testing functions for field_redirection.
 */
trait FieldRedirectionTestTrait {

  /**
   * Sets up a content type with a link field.
   *
   * @return \Drupal\node\Entity\NodeType
   *   Created content type.
   */
  public function setupContentTypeAndField() {
    $contentType = $this->drupalCreateContentType();
    $storage = FieldStorageConfig::create([
      'field_name' => 'url',
      'entity_type' => 'node',
      'type' => 'link',
      'cardinality' => 1,
    ]);
    $storage->save();
    FieldConfig::create([
      'field_storage' => $storage,
      'label' => 'URL',
      'bundle' => $contentType->id(),
      'settings' => [
        'title' => DRUPAL_DISABLED,
        'link_type' => LinkItemInterface::LINK_GENERIC,
      ],
    ])->save();

    return $contentType;
  }

}
