<?php

declare(strict_types=1);

namespace Drupal\auditfiles\Reference;

/**
 * Represents an entry on disk.
 */
final class DiskReference implements ReferenceInterface {

  /**
   * Constructs a new DiskReference.
   */
  private function __construct(
    private readonly string $uri,
  ) {
  }

  /**
   * Creates a new DiskReference.
   */
  public static function create(string $uri): static {
    return new static($uri);
  }

  /**
   * Get the URI.
   */
  public function getUri(): string {
    return $this->uri;
  }

  /**
   * Prints a string useful for debugging.
   */
  public function __toString(): string {
    return sprintf('File on disk at: %s', $this->uri);
  }

}
