<?php

namespace Drupal\entity_pager;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\Core\Utility\Token;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;

/**
 * Entity pager object.
 */
class EntityPager implements EntityPagerInterface {

  use StringTranslationTrait;

  /**
   * Entity pager options.
   *
   * @var array
   */
  protected $options;

  /**
   * The executable for the view that the pager is attached to.
   *
   * @var \Drupal\views\ViewExecutable
   */
  protected $view;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * EntityPager constructor.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view object.
   * @param array $options
   *   An array of options for the EntityPager.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   */
  public function __construct(ViewExecutable $view, array $options, Token $token) {
    $this->view = $view;
    $this->options = $options;
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public function getView() {
    return $this->view;
  }

  /**
   * {@inheritdoc}
   */
  public function getLinks() {
    return [
      'prev' => $this->getLink('link_prev', -1),
      'all' => $this->getAllLink(),
      'next' => $this->getLink('link_next', 1),
      'count' => $this->getCount(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCountWord() {
    $count = 'invalid';

    if (isset($this->getView()->total_rows)) {
      $total = $this->getView()->total_rows;
      if ($total === 0) {
        $count = 'none';
      }
      elseif ($total === 1) {
        $count = 'one';
      }
      else {
        $count = 'many';
      }
    }

    return $count;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity() {
    $entity = NULL;

    $route_match = \Drupal::routeMatch();
    $route = $route_match->getRouteObject();
    if ($route) {
      $parameters = $route->getOption('parameters');
      if ($parameters) {
        foreach ($parameters as $name => $options) {
          if (isset($options['type']) && strpos($options['type'], 'entity:') === 0) {
            $candidate = $route_match->getParameter($name);
            if ($candidate instanceof ContentEntityInterface && $candidate->hasLinkTemplate('canonical')) {
              $entity = $candidate;
              break;
            }
          }
        }
      }
    }

    if (!$entity && \Drupal::request()->attributes->has('entity')) {
      $entity = \Drupal::request()->attributes->get('entity');
    }

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    return $this->options;
  }

  /**
   * Returns the currently active row from the view results.
   *
   * @return bool|int
   *   The index of the active row, or FALSE
   */
  protected function getCurrentRow() {
    $entity = $this->getEntity();

    /** @var \Drupal\views\ResultRow $result */
    foreach ($this->getView()->result as $index => $result) {
      $resultEntity = $this->getResultEntity($result);

      if (!is_null($entity) && $resultEntity->id() === $entity->id()) {
        return $index;
      }
    }

    return FALSE;
  }

  /**
   * Returns the result row at the index specified.
   *
   * @param int $index
   *   The index of the result row to return from the view.
   *
   * @return \Drupal\views\ResultRow|null
   *   The result row, or NULL.
   */
  protected function getResultRow($index) {
    $result_row = NULL;

    if (isset($this->view->result[$index])) {
      $result_row = $this->view->result[$index];
    }
    elseif ($this->options['circular_paging']) {
      $result_row = $index < 0
        ? $this->view->result[count($this->view->result) - 1]
        : $this->view->result[0];
    }

    return $result_row;
  }

  /**
   * Returns a Display All link render array.
   *
   * @return array
   *   The element to render.
   */
  protected function getAllLink() {
    $link = [];

    if ($this->options['display_all']) {
      $entity = $this->getEntity();
      $url = $this->detokenize($this->options['link_all_url'], $entity);

      $url_scheme = parse_url($url, PHP_URL_SCHEME);
      if (!$url_scheme) {
        if (!in_array(substr($url, 0, 1), ['/', '#', '?'])) {
          $url = '/' . $url;
        }

        $url = urldecode($url);
      }

      $link = [
        '#type' => 'link',
        '#title' => [
          '#markup' => $this->detokenize($this->options['link_all_text'], $entity),
        ],
        '#url' => $url_scheme ? Url::fromUri($url) : Url::fromUserInput($url),
      ];
    }

    return $link;
  }

  /**
   * Returns an Entity pager link.
   *
   * @param string $name
   *   The name of the link to return.
   * @param int $offset
   *   The offset from the current row that this link should link to.
   *
   * @return array
   *   The render array for the specified link.
   */
  protected function getLink($name, $offset = 0) {
    $row = $this->getResultRow($this->getCurrentRow() + $offset);
    $disabled = !is_object($row);
    $entity = $disabled ? NULL : $this->getResultEntity($row);

    $title = $this->detokenize($this->options[$name], $entity);

    if (!$disabled || $this->options['show_disabled_links']) {
      $pager_link = new EntityPagerLink($title, $entity);
      $link = $pager_link->getLink();
    }
    else {
      $link = [];
    }

    return $link;
  }

  /**
   * Returns a render array for a count of all items.
   *
   * @return array
   *   The render array for the item count.
   */
  protected function getCount() {
    $count = [];

    if ($this->options['display_count']) {
      $current = $this->getCurrentRow();

      $count = [
        '#type' => 'markup',
        '#markup' => $this->t('@cnt of <span class="total">@count</span>', [
          '@cnt' => number_format($current + 1),
          '@count' => number_format($this->view->total_rows),
        ]),
      ];
    }

    return $count;
  }

  /**
   * Replaces all tokens in provided string.
   *
   * Supports the current entity from the request object.
   *
   * @param string $string
   *   The string to de-tokenize.
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   The entity to use for de-tokenization.
   *
   * @return string
   *   The de-tokenized string.
   */
  protected function detokenize($string, $entity) {
    if (is_null($entity)) {
      $entity = $this->getEntity();
    }

    $data = [];
    if ($entity instanceof EntityInterface) {
      $data[$entity->getEntityTypeId()] = $entity;
    }

    return $this->token->replace($string, $data, ['clear' => TRUE]);
  }

  /**
   * Get the entity from the current views row.
   *
   * @param \Drupal\views\ResultRow $row
   *   The views result row object.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The content entity from the result.
   */
  protected function getResultEntity(ResultRow $row) {
    return $this->options['relationship']
      ? $row->_relationship_entities[$this->options['relationship']]
      : $row->_entity;
  }

}
