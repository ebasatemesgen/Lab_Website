<?php

namespace Drupal\field_redirection;

use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Defines a value object for a field redirection result.
 *
 * @see \Drupal\field_redirection\FieldRedirectionResultBuilder
 */
class FieldRedirectionResult {

  /**
   * TRUE if redirect should occur.
   *
   * @var bool
   */
  protected $shouldRedirect = TRUE;

  /**
   * URL to redirect to.
   *
   * @var \Drupal\Core\Url
   */
  protected $redirectUrl;

  /**
   * Constructs a new FieldRedirectionResult.
   *
   * Note this function is protected by design, use one of the static methods
   * such as ::fromUrl and ::deny.
   */
  protected function __construct() {}

  /**
   * Returns TRUE if redirect should occur.
   *
   * @return bool
   *   TRUE if redirect should occur.
   */
  public function shouldRedirect() {
    return $this->shouldRedirect;
  }

  /**
   * Gets the result as a redirect response.
   *
   * @param int $status_code
   *   Status code.
   * @param array $headers
   *   Additional headers.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect response.
   *
   * @throws \LogicException
   *   When the result should not redirect.
   */
  public function asRedirectResponse($status_code = 302, array $headers = []) {
    return new RedirectResponse($this->getRedirectUrl()->toString(), $status_code, $headers);
  }

  /**
   * Gets redirect URL.
   *
   * @return \Drupal\Core\Url
   *   The url object.
   */
  protected function getRedirectUrl() {
    if (!$this->shouldRedirect()) {
      throw new \LogicException("There is no redirect URL for a redirect result that doesn't redirect");
    }
    return $this->redirectUrl;
  }

  /**
   * Factory method for a field redirection result that should not redirect.
   *
   * @return \Drupal\field_redirection\FieldRedirectionResult
   *   New instance.
   */
  public static function deny() {
    $instance = new static();
    $instance->shouldRedirect = FALSE;
    return $instance;
  }

  /**
   * Factory method to create from a URL object.
   *
   * @param \Drupal\Core\Url $url
   *   The URL object.
   *
   * @return \Drupal\field_redirection\FieldRedirectionResult
   *   New instance.
   */
  public static function fromUrl(Url $url) {
    $instance = new static();
    $instance->redirectUrl = $url;
    return $instance;
  }

}
