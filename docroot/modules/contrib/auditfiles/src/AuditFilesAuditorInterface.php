<?php

declare(strict_types=1);

namespace Drupal\auditfiles;

/**
 * Common interface for auditor services.
 *
 * @template-covariant R of \Drupal\auditfiles\Reference\ReferenceInterface
 */
interface AuditFilesAuditorInterface {

  /**
   * Retrieves the references to operate on.
   *
   * @phpstan-return \Generator<R>
   */
  public function getReferences(): \Generator;

}
