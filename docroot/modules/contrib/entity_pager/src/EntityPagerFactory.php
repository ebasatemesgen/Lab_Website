<?php

namespace Drupal\entity_pager;

use Drupal\Core\Utility\Token;
use Drupal\views\ViewExecutable;

/**
 * Factory for entity pager objects.
 */
class EntityPagerFactory {

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Default options.
   *
   * @var array
   */
  // phpcs:ignore -- Cannot change member name as this would be an API change.
  protected $default_options = [
    'link_next' => 'next >',
    'link_prev' => '< prev',
    'link_all_url' => '<front>',
    'link_all_text' => 'Home',
    'display_all' => TRUE,
    'display_count' => TRUE,
    'show_disabled_links' => TRUE,
    'circular_paging' => FALSE,
    'log_performance' => TRUE,
  ];

  /**
   * EntityPagerFactory constructor.
   *
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   */
  public function __construct(Token $token) {
    $this->token = $token;
  }

  /**
   * Returns a newly constructed entity pager.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The executable to construct an entity pager for.
   * @param array $options
   *   (optional) Options for the entity pager.
   *
   * @return \Drupal\entity_pager\EntityPagerInterface
   *   The entity pager object.
   */
  public function get(ViewExecutable $view, array $options = []) {
    $options = (empty($options))
      ? $this->default_options
      : array_merge($this->default_options, $options);

    return new EntityPager($view, $options, $this->token);
  }

}
