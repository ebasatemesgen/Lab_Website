<?php

declare(strict_types=1);

namespace Drupal\auditfiles\Reference;

/**
 * Common interface for references.
 *
 * Objects implementing this must be serialisable, as they will enter the batch
 * system intact.
 */
interface ReferenceInterface {

}
