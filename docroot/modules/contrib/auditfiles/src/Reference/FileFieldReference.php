<?php

declare(strict_types=1);

namespace Drupal\auditfiles\Reference;

/**
 * Represents an entry from a file field row.
 *
 * This includes derivative types of FileItem, such as ImageItem.
 *
 * @see \Drupal\file\Plugin\Field\FieldType\FileItem
 * @see \Drupal\image\Plugin\Field\FieldType\ImageItem
 */
final class FileFieldReference implements ReferenceInterface {

  private readonly FileEntityReference $fileEntityReference;

  /**
   * Constructs a new FileFieldReference.
   */
  private function __construct(
    public mixed $table,
    public mixed $column,
    public string|int $entityId,
    private int $fileId,
    public string $entityTypeId,
    public string $bundle,
  ) {
    $this->fileEntityReference = FileEntityReference::create($fileId);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    mixed $table,
    mixed $column,
    string|int $entityId,
    int $fileId,
    string $entityTypeId,
    string $bundle,
  ): static {
    return new static($table, $column, $entityId, $fileId, $entityTypeId, $bundle);
  }

  /**
   * Get source entity ID.
   */
  public function getSourceEntityId(): string|int {
    return $this->entityId;
  }

  /**
   * Get source entity type.
   */
  public function getSourceEntityTypeId(): string {
    return $this->entityTypeId;
  }

  /**
   * Get source bundle.
   */
  public function getSourceBundle(): string {
    return $this->bundle;
  }

  /**
   * Get file reference.
   */
  public function getFileReference(): FileEntityReference {
    return $this->fileEntityReference;
  }

  /**
   * Prints a string useful for debugging.
   */
  public function __toString(): string {
    return sprintf('Reference at %s from entity %s #%s to file #%s', $this->table, $this->entityTypeId, $this->entityId, $this->fileId);
  }

}
