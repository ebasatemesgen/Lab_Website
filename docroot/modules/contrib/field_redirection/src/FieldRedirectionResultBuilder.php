<?php

namespace Drupal\field_redirection;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\Core\Utility\Token;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\file\Plugin\Field\FieldType\FileItem;
use Drupal\link\Plugin\Field\FieldType\LinkItem;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;

/**
 * Defines a service for evaluating the intended action for a field redirection.
 *
 * There are a large number of scenarios to consider in determining what action
 * to take for a field redirection. The final result is conveyed with a field
 * redirection result value object. This class is used to build the value object
 * based on the field value and formatter settings.
 *
 * We use a separate helper class to the formatter so that we can kernel test
 * the scenarios without needing to use a browser to redirect.
 *
 * @see \Drupal\field_redirection\FieldRedirectionResult
 */
class FieldRedirectionResultBuilder {

  use StringTranslationTrait;

  /**
   * Path Matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * Token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * State service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a new FieldRedirectionResultBuilder.
   *
   * @param \Drupal\Core\Path\PathMatcherInterface $pathMatcher
   *   Path matcher.
   * @param \Drupal\Core\Utility\Token $token
   *   Token service.
   * @param \Drupal\Core\State\StateInterface $state
   *   State service.
   */
  public function __construct(PathMatcherInterface $pathMatcher, Token $token, StateInterface $state) {
    $this->pathMatcher = $pathMatcher;
    $this->token = $token;
    $this->state = $state;
  }

  /**
   * Determine whether we should deny redirecting.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field items.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account.
   * @param array $settings
   *   The field settings.
   *
   * @return bool
   *   TRUE if we should deny redirecting.
   */
  protected function shouldDeny(FieldItemListInterface $items, Request $request, AccountInterface $account, array $settings = []) {
    // Return early if account has bypass permission.
    if ($account->hasPermission('bypass redirection')) {
      return TRUE;
    }

    $current_url = Url::fromRoute('<current>');
    $current_path = ltrim($current_url->getInternalPath(), '/');

    // Optionally control the list of pages this works on.
    if (!empty($settings['page_restrictions']) && !empty($settings['pages'])) {
      // Remove '1' from this value so it can be XOR'd later on.
      $page_restrictions = $settings['page_restrictions'] - 1;

      // Do raw token replacements.
      $pages = $this->token->replace(
        $settings['pages'],
        [],
        ['clear' => TRUE]
      );

      // Normalise all paths to lower case.
      $pages = mb_strtolower($pages);
      $page_match = $this->pathMatcher->matchPath($current_path, $pages);

      $requestUri = $request->getRequestUri();
      if ($current_path != $requestUri) {
        $page_match = $page_match || $this->pathMatcher->matchPath($requestUri, $pages);
      }

      // Stop processing if the page restrictions have matched.
      if (!($page_restrictions xor $page_match)) {
        return TRUE;
      }
    }

    // Don't do anything if the current page is running the normal cron script;
    // this also supports Elysia Cron.
    if (str_starts_with($current_path, 'cron')) {
      return TRUE;
    }
    // Don't do anything if the cron script is being executed from the admin
    // status page.
    if ($current_path === 'admin/reports/status/run-cron') {
      return TRUE;
    }
    // Don't do anything if site is in maintenance mode.
    if (defined('MAINTENANCE_MODE') || $this->state->get('system.maintenance_mode')) {
      return TRUE;
    }

    // Get the URL to redirect to.
    if (!$items->isEmpty()) {
      /** @var \Drupal\Core\Url $redirect_url */
      $redirect_url = $this->getUrl($items);
    }
    // If no URL was provided, and the user does not have permission to bypass
    // the redirection, display the 404 error page.
    elseif (isset($settings['404_if_empty']) && $settings['404_if_empty']) {
      throw new NotFoundHttpException();
    }
    // If no values are present, pick up ball and go home.
    else {
      return TRUE;
    }

    // We need to check if the redirect URL is the same as:
    //
    // 1. The current (possibly an alias) path (relative).
    // 2. The current path's internal path (relative). Url->toString()
    // always returns an alias, so this is covered by point 1 above.
    // 3. The current path's internal path (absolute).
    // 4. The current path, which is also the home page.
    //
    // If any of these cases are true, then do not redirect.
    //
    // Current path (relative) and current internal path (relative).
    if ($current_path == ltrim($redirect_url->toString(), '/')) {
      return TRUE;
    }
    // Current path (absolute).
    $current_url->setAbsolute(TRUE);
    if ($current_url->toString() === $redirect_url->toString()) {
      return TRUE;
    }

    // Current path is the home page.
    if (!$redirect_url->isExternal() && ($redirect_url->getRouteName() == '<front>') && ($this->pathMatcher->isFrontPage())) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Builds a redirection result for a given set of values.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field items.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account.
   * @param array $settings
   *   The field settings.
   *
   * @return \Drupal\field_redirection\FieldRedirectionResult
   *   The redirection result.
   */
  public function buildResult(FieldItemListInterface $items, Request $request, AccountInterface $account, array $settings = []) {
    if ($this->shouldDeny($items, $request, $account, $settings)) {
      return FieldRedirectionResult::deny();
    }

    return FieldRedirectionResult::fromUrl($this->getUrl($items));
  }

  /**
   * Provide the destination URL for the redirect.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field items.
   *
   * @return \Drupal\Core\Url
   *   A URL object representing the redirect destination.
   *
   * @throws \LogicException
   *   When the FieldItemInstance is not supported for field redirection.
   */
  public function getUrl(FieldItemListInterface $items) {
    $item = $items->first();
    if ($item instanceof LinkItem) {
      return $item->getUrl();
    }
    elseif ($item instanceof FileItem) {
      $fileUrl = $item->entity->createFileUrl(FALSE);
      return Url::fromUri($fileUrl);
    }
    elseif ($item instanceof EntityReferenceItem) {
      return $items->referencedEntities()[0]->toUrl();
    }
    throw new \LogicException("Field redirection not supported for FieldItemInstance of type " . get_class($item) . ".");
  }

}
