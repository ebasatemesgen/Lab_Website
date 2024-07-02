<?php

namespace Drupal\imagick\Plugin\ImageToolkit\Operation\imagick;

use Imagick;

/**
 * Defines imagick transparent background operation.
 *
 * @ImageToolkitOperation(
 *   id = "imagick_transparent_background",
 *   toolkit = "imagick",
 *   operation = "transparent_background",
 *   label = @Translation("Transparent background"),
 *   description = @Translation("Make image background transparent")
 * )
 */
class TransparentBackground extends ImagickOperationBase {

  /**
   * {@inheritdoc}
   */
  protected function arguments() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
    protected function execute(array $arguments = []) {
        /* @var $resource Imagick */
        $resource = $this->getToolkit()->getResource();

        if ($resource->getImageFormat() === 'PNG') {
            return TRUE;
        }

        $resource->setImageFormat('PNG');

        return $this->removeBackground($resource, "rgb(255,255,255)") &&
            $this->removeBackground($resource, "rgb(0,0,0)");
    }

    private function removeBackground(Imagick $resource, $target_color): bool {
        // Create border around image to link background areas
        $resource->borderImage($target_color, 1, 1);
        // Replace white background with fuchsia
        $floodSuccess = $resource->floodFillPaintImage("rgb(255, 0, 255)", 2500, $target_color, 0 , 0, false);
        // Remove previously created border
        $resource->shaveImage(1, 1);
        // Make fuchsia transparent
        $transparentSuccess = $resource->transparentPaintImage("rgb(255,0,255)", 0, 10, FALSE);

        return ($floodSuccess && $transparentSuccess);
    }
}
