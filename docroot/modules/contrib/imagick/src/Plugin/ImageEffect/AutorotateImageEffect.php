<?php

namespace Drupal\imagick\Plugin\ImageEffect;

use Drupal\Core\Image\ImageInterface;
use Drupal\image\ImageEffectBase;
use Imagick;

/**
 * Autorotates an image resource.
 *
 * @ImageEffect(
 *   id = "image_autorotate",
 *   label = @Translation("Autorotate"),
 *   description = @Translation("Autorotates an image using EXIF data.")
 * )
 */
class AutorotateImageEffect extends ImageEffectBase {

  /**
   * {@inheritdoc}
   */
  public function applyEffect(ImageInterface $image) {
    if (!$image->apply('autorotate')) {
      $this->logger->error('Image autorotate failed using the %toolkit toolkit on %path (%mimetype)', [
        '%toolkit' => $image->getToolkitId(),
        '%path' => $image->getSource(),
        '%mimetype' => $image->getMimeType()
      ]);
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function transformDimensions(array &$dimensions, $uri) {
    try {
      $image = new Imagick($uri);
      $orientation = $image->getImageOrientation();
      switch ($orientation) {
        // If the image is rotated 90° or 270°, swap the width and height.
        case Imagick::ORIENTATION_RIGHTTOP:
        case Imagick::ORIENTATION_LEFTTOP:
        case Imagick::ORIENTATION_LEFTBOTTOM:
        case Imagick::ORIENTATION_RIGHTBOTTOM:
          $dimensions = [
            'width' => $dimensions['height'],
            'height' => $dimensions['width'],
          ];
      }
    }
    catch (\ImagickException $e) {
      $this->logger->error('Image autorotate failed to transform dimensions for %uri: @message', [
        '%uri' => $uri,
        '@message' => $e->getMessage(),
      ]);
    }
  }

}
