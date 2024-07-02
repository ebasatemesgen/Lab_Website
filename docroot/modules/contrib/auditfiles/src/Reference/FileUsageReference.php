<?php

declare(strict_types=1);

namespace Drupal\auditfiles\Reference;

/**
 * Represents an entry in the file usage table.
 */
final class FileUsageReference implements ReferenceInterface {

  /**
   * Constructs a new FileUsageReference.
   */
  private function __construct(
    private readonly int $fileId,
    private readonly string $module,
    private readonly string $entityTypeId,
    private readonly string|int $entityId,
    private readonly int $count,
  ) {
  }

  /**
   * Create a FileUsageReference.
   */
  public static function create(int $fileId, string $module, string $entityTypeId, string|int $entityId, int $count): static {
    return new static($fileId, $module, $entityTypeId, $entityId, $count);
  }

  /**
   * @phpstan-param object{fid: string, module: string, type: string, id: string|int, count: string} $row
   */
  public static function createFromRow(object $row): static {
    return static::create((int) $row->fid, $row->module, $row->type, $row->id, (int) $row->count);
  }

  /**
   * Prints a string useful for debugging.
   */
  public function __toString(): string {
    return sprintf('File usage');
  }

  /**
   * Get file ID.
   */
  public function getFileId(): int {
    return $this->fileId;
  }

  /**
   * Get module.
   */
  public function getModule(): string {
    return $this->module;
  }

  /**
   * Get entity type ID.
   */
  public function getEntityTypeId(): string {
    return $this->entityTypeId;
  }

  /**
   * Get entity ID.
   */
  public function getEntityId(): int|string {
    return $this->entityId;
  }

  /**
   * Get count.
   */
  public function getCount(): int {
    return $this->count;
  }

}
