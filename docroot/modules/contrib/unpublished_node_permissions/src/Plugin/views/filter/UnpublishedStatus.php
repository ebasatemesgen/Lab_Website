<?php

namespace Drupal\unpublished_node_permissions\Plugin\views\filter;

use Drupal\node\Entity\NodeType;
use Drupal\node\Plugin\views\filter\Status;

/**
 * Filter by unpublished status.
 *
 * @ingroup views_filter_handlers
 */
class UnpublishedStatus extends Status {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $table = $this->ensureMyTable();

    $query = "{$table}.status = 1 OR ({$table}.uid = ***CURRENT_USER*** AND ***CURRENT_USER*** <> 0 AND ***VIEW_OWN_UNPUBLISHED_NODES*** = 1) OR ***VIEWUNPUBLISHED_ANY*** = 1 OR  ***BYPASS_NODE_ACCESS*** = 1";
    if ($this->moduleHandler->moduleExists('content_moderation')) {
      $query .= ' OR ***VIEW_ANY_UNPUBLISHED_NODES*** = 1';
    }

    $types = [];
    /** @var \Drupal\node\NodeTypeInterface $type */
    foreach (NodeType::loadMultiple() as $type) {
      $type_id = $type->id();
      $types[] = "({$table}.type = '{$type_id}' AND ***VIEWUNPUBLISHED_TYPE_{$type_id}*** = 1)";
    }

    if ($types !== []) {
      $types = implode(' OR ', $types);
      $query .= " OR $types";
    }

    $this->query->addWhereExpression($this->options['group'], $query);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();
    $contexts[] = 'user.roles';

    return $contexts;
  }

}
