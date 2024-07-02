<?php

namespace Drupal\purge_invalidation_form\Plugin\Purge\Processor;

use Drupal\purge\Plugin\Purge\Processor\ProcessorBase;
use Drupal\purge\Plugin\Purge\Processor\ProcessorInterface;

/**
 * Processor for the 'InvalidationManager->invalidate' function.
 *
 * @PurgeProcessor(
 *   id = "invalidation_form",
 *   label = @Translation("Invalidation Form Processor"),
 *   description = @Translation("Processor for the 'InvalidationManager->invalidate' function."),
 *   enable_by_default = true,
 *   configform = "",
 * )
 */
class FormInvalidateProcessor extends ProcessorBase implements ProcessorInterface {

}
