<?php

declare(strict_types=1);

namespace Drupal\auditfiles\Reference;

use Drupal\file\Entity\File;
use Drupal\file\FileInterface;

/**
 * Represents a file entity.
 */
final class FileEntityReference implements ReferenceInterface {

  /**
   * Constructs a new FileEntityReference.
   */
  private function __construct(
    private readonly int $id,
  ) {
  }

  /**
   * Creates a file entity reference.
   */
  public static function create(int $id): static {
    return new static($id);
  }

  /**
   * Get the file entity ID.
   */
  public function getId(): int {
    return $this->id;
  }

  /**
   * Get the file entity.
   */
  public function getFile(): ?FileInterface {
    return File::load($this->id);
  }

  /**
   * Prints a string useful for debugging.
   */
  public function __toString(): string {
    return sprintf('File entity: %s', $this->id);
  }

}
