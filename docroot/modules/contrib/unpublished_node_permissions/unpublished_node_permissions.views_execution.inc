<?php

/**
 * @file
 * Contains unpublished_node_permissions.views_execution.inc.
 */

use Drupal\node\Entity\NodeType;
use Drupal\views\ViewExecutable;

/**
 * Implements hook_views_query_substitutions().
 */
function unpublished_node_permissions_views_query_substitutions(ViewExecutable $view) {
  $account = \Drupal::currentUser();

  $substitutions = [];
  /** @var \Drupal\node\NodeTypeInterface $type */
  foreach (NodeType::loadMultiple() as $type) {
    $type_id = $type->id();
    $substitutions["***VIEWUNPUBLISHED_TYPE_{$type_id}***"] = (int) $account->hasPermission("view {$type_id} unpublished content");
  }

  $substitutions["***VIEWUNPUBLISHED_ANY***"] = (int) $account->hasPermission('view unpublished content');

  return $substitutions;
}
