<?php

declare(strict_types=1);

namespace Drupal\auditfiles\Batch;

use Drupal\Core\Messenger\MessengerInterface;

/**
 * Batch trait.
 */
trait AuditFilesBatchTrait {

  /**
   * Finalize batch.
   *
   * @param bool $success
   *   A boolean indicating whether the re-build process has completed.
   * @param array $results
   *   An array of results information.
   * @param array $operations
   *   An array of function calls (not used in this function).
   */
  public static function finishBatch(bool $success, array $results, array $operations): void {
    if (!$success) {
      $error_operation = reset($operations);
      static::getMessenger()->addError(\t('An error occurred while processing @operation with arguments : @args', [
        '@operation' => $error_operation[0],
        '@args' => print_r($error_operation[0], TRUE),
      ]));
    }
  }

  /**
   * The messenger service.
   */
  protected static function getMessenger(): MessengerInterface {
    // @phpstan-ignore-next-line
    return \Drupal::service('messenger');
  }

}
