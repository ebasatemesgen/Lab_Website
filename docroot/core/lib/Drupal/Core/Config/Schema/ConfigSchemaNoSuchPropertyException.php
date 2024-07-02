<?php

namespace Drupal\Core\Config\Schema;

/**
 * The exception to raise when the config schema has no given property.
 */
class ConfigSchemaNoSuchPropertyException extends \InvalidArgumentException {

  /**
   * The name of a missing property.
   *
   * @var string
   */
  protected $property;

  /**
   * Constructs the exception.
   *
   * @param string $property
   *   The name of a missing property.
   * @param string $message
   *   The exception message.
   * @param int $code
   *   The exception code.
   * @param \Throwable|null $previous
   *   The previous throwable used for the exception chaining.
   */
  public function __construct(string $property, string $message = '', int $code = 0, \Throwable $previous = NULL) {
    parent::__construct($message, $code, $previous);
    $this->property = $property;
  }

  /**
   * Returns the name of a missing property.
   *
   * @return string
   *   The name of a missing property.
   */
  public function getProperty(): string {
    return $this->property;
  }

}
