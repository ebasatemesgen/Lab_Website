<?php

/**
 * @file
 * Contains unpublished_node_permissions.views.inc.
 */

use Drupal\unpublished_node_permissions\Plugin\views\filter\UnpublishedStatus;

/**
 * Implements hook_views_plugins_filter_alter().
 */
function unpublished_node_permissions_views_plugins_filter_alter(array &$plugins) {
  if (isset($plugins['node_status'])) {
    $plugins['node_status']['class'] = UnpublishedStatus::class;
  }
}
